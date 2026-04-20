<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Content\ProfileRepository;
use App\Domain\Content\TreeRepository;
use App\Domain\Intent\IntentEngine;
use App\Domain\Intent\IntentModel;
use App\Domain\Session\SessionManager;
use App\Core\Database;

class InteractionController
{
    private SessionManager $sessions;

    public function __construct()
    {
        $trees   = new TreeRepository();
        $model   = new IntentModel();
        $profiles = new ProfileRepository();
        $engine  = new IntentEngine($trees, $model, $profiles);
        $this->sessions = new SessionManager($trees, $engine);
    }

    // ── Start nieuwe sessie ───────────────────────────────────────────────────
    public function start(int $forcedTreeId = 0): void
    {
        $profileId    = (int)($_GET['profile'] ?? 1);
        $forcedTreeId = $forcedTreeId ?: (int)($_GET['tree'] ?? 0);
        $session      = $this->sessions->start($profileId, $forcedTreeId);
        $_SESSION['session_id'] = $session->id;

        $trees  = new TreeRepository();
        $model  = new IntentModel();
        $engine = new IntentEngine($trees, $model, new ProfileRepository());
        $result = $engine->getNextOptions($session, null);

        if ($result->isPending) {
            $this->render('session_waiting', [
                'jobId'    => $result->jobId,
                'sentence' => '',
                'view'     => 'session_waiting',
            ]);
            return;
        }

        $this->renderSession($session->id, $result->options, '');
    }

    // ── Gebruiker selecteert een optie ────────────────────────────────────────
    public function select(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        $nodeId    = (int)($_POST['node_id'] ?? 0);

        if (!$sessionId || !$nodeId) { $this->redirectHome(); return; }

        $result = $this->sessions->select($sessionId, $nodeId);

        if ($result->newState === 'CONFIRMING') {
            $this->render('session_confirm', [
                'sentence'         => $result->sentence,
                'suggestedMessage' => $result->suggestedMessage,
                'view'             => 'session_confirm',
            ]);
            return;
        }

        if ($result->options->isPending) {
            $this->render('session_waiting', [
                'jobId'    => $result->options->jobId,
                'sentence' => $result->sentence,
                'view'     => 'session_waiting',
            ]);
            return;
        }

        $this->renderSession($sessionId, $result->options->options, $result->sentence);
    }

    // ── Stap terug ────────────────────────────────────────────────────────────
    public function back(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        if (!$sessionId) { $this->redirectHome(); return; }

        $result = $this->sessions->back($sessionId);

        if ($result->options->isPending) {
            $this->render('session_waiting', [
                'jobId'    => $result->options->jobId,
                'sentence' => $result->sentence,
                'view'     => 'session_waiting',
            ]);
            return;
        }

        $this->renderSession($sessionId, $result->options->options, $result->sentence);
    }

    // ── Bevestigen (ja) ───────────────────────────────────────────────────────
    public function confirm(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        $sentence  = $_POST['sentence'] ?? '';
        if ($sessionId) $this->sessions->complete($sessionId);
        unset($_SESSION['session_id']);

        $this->render('session_complete', [
            'sentence' => $sentence,
            'view'     => 'session_complete',
        ]);
    }

    // ── Afwijzen (nee, opnieuw) ───────────────────────────────────────────────
    public function reject(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        if (!$sessionId) { $this->redirectHome(); return; }

        $result = $this->sessions->reject($sessionId);

        if ($result->options->isPending) {
            $this->render('session_waiting', [
                'jobId'    => $result->options->jobId,
                'sentence' => $result->sentence,
                'view'     => 'session_waiting',
            ]);
            return;
        }

        $this->renderSession($sessionId, $result->options->options, $result->sentence);
    }

    // ── Poll-endpoint: status van een dynamic_options job ────────────────────
    // Geeft JSON terug: {status, options} of {status:'pending'}
    public function dynamicStatus(): void
    {
        header('Content-Type: application/json');
        $jobId     = (int)($_GET['job'] ?? 0);
        $sessionId = $_SESSION['session_id'] ?? null;

        if (!$jobId || !$sessionId) {
            echo json_encode(['status' => 'error']); return;
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("SELECT status, result_json FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job  = $stmt->fetch();

        if (!$job || $job['status'] === 'pending' || $job['status'] === 'processing') {
            echo json_encode(['status' => 'pending']); return;
        }

        if ($job['status'] === 'failed') {
            echo json_encode(['status' => 'failed']); return;
        }

        // Job klaar: verwerk resultaat en sla nodes op
        $data       = json_decode($job['result_json'], true);
        $isComplete = (bool)($data['is_complete'] ?? false);
        $rawOptions = $data['options'] ?? [];

        $aiOptions = array_map(fn($opt) => [
            'label'             => $opt['label'] ?? '',
            'image_url'         => $opt['image_url'] ?? null,
            'is_leaf'           => $isComplete ? 1 : 0,
            'suggested_message' => $opt['suggested_message'] ?? null,
        ], $rawOptions);

        $result = $this->sessions->applyDynamicResult($sessionId, $aiOptions);

        echo json_encode([
            'status'   => 'done',
            'redirect' => BASE_URL . '?action=session_show',
        ]);

        // Sla opties tijdelijk in sessie op zodat session_show ze kan tonen
        $_SESSION['pending_options']  = $result->options->options;
        $_SESSION['pending_sentence'] = $result->sentence;
    }

    // ── Toon opties na dynamic poll ───────────────────────────────────────────
    public function show(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        $options   = $_SESSION['pending_options']  ?? [];
        $sentence  = $_SESSION['pending_sentence'] ?? '';
        unset($_SESSION['pending_options'], $_SESSION['pending_sentence']);

        if (!$sessionId || empty($options)) { $this->redirectHome(); return; }

        $this->renderSession($sessionId, $options, $sentence);
    }

    // ── Reset dynamische boom (verwijdert alle gegenereerde nodes) ───────────
    public function resetDynamic(): void
    {
        unset($_SESSION['session_id']);

        $db = \App\Core\Database::getConnection();
        $stmt = $db->query(
            "SELECT id FROM option_trees WHERE generation_mode = 'dynamic' AND status = 'ready' LIMIT 1"
        );
        $tree = $stmt->fetch();
        if ($tree) {
            $db->prepare("DELETE FROM tree_nodes WHERE tree_id = ?")->execute([$tree['id']]);
        }

        header("Location: " . BASE_URL);
        exit;
    }

    // ── Restore sessie of toon home ───────────────────────────────────────────
    public function restore(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        if ($sessionId) {
            $result = $this->sessions->restore($sessionId);
            if ($result) {
                if ($result->options->isPending) {
                    $this->render('session_waiting', [
                        'jobId'    => $result->options->jobId,
                        'sentence' => $result->sentence,
                        'view'     => 'session_waiting',
                    ]);
                    return;
                }
                $this->renderSession($sessionId, $result->options->options, $result->sentence);
                return;
            }
        }

        // Geen actieve sessie — toon altijd het keuzescherm
        $trees       = (new \App\Domain\Content\TreeRepository())->getAllReady();
        $dynamicTree = null;
        $staticTrees = [];
        foreach ($trees as $tree) {
            if ($tree['generation_mode'] === 'dynamic') {
                $dynamicTree = $tree;
            } else {
                $staticTrees[] = $tree;
            }
        }
        $this->render('home', [
            'dynamicTree' => $dynamicTree,
            'staticTrees' => $staticTrees,
            'view'        => 'home',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function renderSession(string $sessionId, array $options, string $sentence): void
    {
        $this->render('session', [
            'sessionId' => $sessionId,
            'options'   => $options,
            'sentence'  => $sentence,
            'view'      => 'session',
        ]);
    }

    private function render(string $view, array $vars = []): void
    {
        extract($vars);
        include __DIR__ . '/../../views/layout.php';
    }

    private function redirectHome(): void
    {
        header('Location: ' . BASE_URL); exit;
    }
}

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
    private SessionManager  $sessions;
    private IntentEngine    $engine;
    private TreeRepository  $trees;

    public function __construct()
    {
        $this->trees    = new TreeRepository();
        $model          = new IntentModel();
        $profiles       = new ProfileRepository();
        $this->engine   = new IntentEngine($this->trees, $model, $profiles);
        $this->sessions = new SessionManager($this->trees, $this->engine);
    }

    // ── Start nieuwe sessie ───────────────────────────────────────────────────
    public function start(int $forcedTreeId = 0): void
    {
        $profileId    = (int)($_GET['profile'] ?? 1);
        $forcedTreeId = $forcedTreeId ?: (int)($_GET['tree'] ?? 0);

        try {
            $session = $this->sessions->start($profileId, $forcedTreeId);
            $_SESSION['session_id']      = $session->id;
            $_SESSION['is_dynamic_tree'] = ($this->trees->getGenerationMode($session->treeId) === 'dynamic');

            $result = $this->engine->getNextOptions($session, null);

            if ($result->isPending) {
                $this->render('session_waiting', [
                    'jobId'    => $result->jobId,
                    'sentence' => '',
                    'view'     => 'session_waiting',
                ]);
                return;
            }

            if (empty($result->options)) {
                // Geen opties beschikbaar (bv. lege statische boom)
                $this->redirectHome();
                return;
            }

            $this->renderSession($session->id, $result->options, '');

        } catch (\Throwable $e) {
            // Toon fout zichtbaar in plaats van stilletjes te redirecten
            error_log('InteractionController::start error: ' . $e->getMessage());
            $this->render('error', ['message' => $e->getMessage(), 'view' => 'error']);
        }
    }

    // ── Gebruiker selecteert een optie ────────────────────────────────────────
    public function select(): void
    {
        $sessionId = $_SESSION['session_id'] ?? null;
        $nodeId    = (int)($_POST['node_id'] ?? 0);
        $isAndere  = isset($_POST['is_andere']);

        if (!$sessionId) { $this->redirectHome(); return; }

        // "Iets anders" — opnieuw genereren met andere opties
        if ($isAndere) {
            try {
                $jobId = $this->sessions->queueAndereOptions($sessionId);
                $this->render('session_waiting', [
                    'jobId'    => $jobId,
                    'sentence' => $_SESSION['pending_sentence'] ?? '',
                    'view'     => 'session_waiting',
                ]);
            } catch (\Throwable $e) {
                error_log('InteractionController::select(andere) error: ' . $e->getMessage());
                $this->render('error', ['message' => $e->getMessage(), 'view' => 'error']);
            }
            return;
        }

        if (!$nodeId) { $this->redirectHome(); return; }

        try {
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

        } catch (\Throwable $e) {
            error_log('InteractionController::select error: ' . $e->getMessage());
            $this->render('error', ['message' => $e->getMessage(), 'view' => 'error']);
        }
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

        if (!$job || in_array($job['status'], ['pending', 'processing'], true)) {
            echo json_encode(['status' => 'pending']); return;
        }

        if (in_array($job['status'], ['failed', 'error'], true)) {
            echo json_encode(['status' => 'failed']); return;
        }

        // Job klaar: verwerk resultaat, sla nodes op, zet sessie-vars VOOR output
        try {
            $data           = json_decode($job['result_json'], true);
            $isComplete     = (bool)($data['is_complete'] ?? false);
            $rawOptions     = $data['options'] ?? [];
            $sentenceSoFar  = trim($data['sentence_so_far'] ?? '');

            $aiOptions = array_map(fn($opt) => [
                'label'             => $opt['label'] ?? '',
                'image_url'         => $opt['image_url'] ?? null,
                'is_leaf'           => $isComplete ? 1 : 0,
                'suggested_message' => $opt['suggested_message'] ?? null,
            ], $rawOptions);

            $result = $this->sessions->applyDynamicResult($sessionId, $aiOptions);

            $sentence = $sentenceSoFar ?: $result->sentence;

            $_SESSION['pending_options']  = $result->options->options;
            $_SESSION['pending_sentence'] = $sentence;

            echo json_encode(['status' => 'done', 'redirect' => BASE_URL . '?action=session_show']);
        } catch (\Throwable $e) {
            error_log('dynamicStatus error: ' . $e->getMessage());
            echo json_encode(['status' => 'failed']);
        }
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

    // ── Home: altijd het keuzescherm, sessie wissen ───────────────────────────
    public function restore(): void
    {
        // Navigeren naar home wist altijd de actieve sessie
        unset($_SESSION['session_id']);

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
        if ($_SESSION['is_dynamic_tree'] ?? false) {
            $hasAndere = !empty(array_filter($options, fn($n) => !empty($n['is_andere'])));
            if (!$hasAndere) {
                $options[] = [
                    'id'                => 0,
                    'label'             => 'Iets anders',
                    'image_url'         => null,
                    'is_leaf'           => 0,
                    'suggested_message' => null,
                    'is_andere'         => true,
                ];
            }
        }
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

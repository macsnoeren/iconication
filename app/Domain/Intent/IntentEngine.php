<?php
declare(strict_types=1);

namespace App\Domain\Intent;

use App\Core\Database;
use App\Domain\Content\TreeRepository;
use App\Domain\Content\ProfileRepository;
use App\Domain\Session\Session;

class IntentEngine
{
    public function __construct(
        private TreeRepository   $trees,
        private IntentModel      $model,
        private ProfileRepository $profiles,
    ) {}

    public function getNextOptions(Session $session, ?int $parentNodeId): OptionsResult
    {
        $mode = $this->trees->getGenerationMode($session->treeId);

        if ($mode === 'dynamic') {
            // Hergebruik bestaande nodes als die al gegenereerd zijn voor deze parent
            $existing = $this->trees->getChildren($session->treeId, $parentNodeId);
            if (!empty($existing)) {
                return OptionsResult::immediate($this->scoreAndSort($existing, $session));
            }
            $jobId = $this->queueDynamicOptions($session, $parentNodeId);
            return OptionsResult::pending($jobId);
        }

        return OptionsResult::immediate($this->getStaticOptions($session, $parentNodeId));
    }

    private function getStaticOptions(Session $session, ?int $parentNodeId): array
    {
        $candidates = $this->trees->getChildren($session->treeId, $parentNodeId);
        return $this->scoreAndSort($candidates, $session);
    }

    private function scoreAndSort(array $candidates, Session $session): array
    {
        // Houd "Iets anders" buiten de sortering zodat het altijd als laatste staat
        // en nooit wegvalt door de limiet.
        $andere  = array_values(array_filter($candidates, fn($n) => $n['label'] === 'Iets anders'));
        $regular = array_values(array_filter($candidates, fn($n) => $n['label'] !== 'Iets anders'));

        foreach ($regular as &$node) {
            $node['score'] = $this->score($node, $session);
        }
        unset($node);
        usort($regular, fn($a, $b) => $b['score'] <=> $a['score']);
        $regular = array_slice($regular, 0, $this->limitForProfile($session->profileId));

        return array_merge($regular, $andere);
    }

    private function score(array $node, Session $session): float
    {
        $base      = $this->model->getWeight($session->profileId, $node['id']);
        $recency   = $this->model->recentlySelected($session->profileId, $node['id']) ? 0.15 : 0.0;
        $timeOfDay = $this->timeOfDayBonus($node['label']);
        return $base + $recency + $timeOfDay;
    }

    private function timeOfDayBonus(string $label): float
    {
        $hour    = (int)date('H');
        $label   = mb_strtolower($label);
        $morning = ['ontbijt', 'koffie', 'thee', 'ochtend'];
        $evening = ['avondeten', 'tv', 'slapen', 'avond'];

        if ($hour < 11) {
            foreach ($morning as $kw) { if (str_contains($label, $kw)) return 0.1; }
        }
        if ($hour > 17) {
            foreach ($evening as $kw) { if (str_contains($label, $kw)) return 0.1; }
        }
        return 0.0;
    }

    private function limitForProfile(int $profileId): int
    {
        return match($this->profiles->getSupportLevel($profileId)) {
            1 => 2,
            3 => 6,
            default => 4,
        };
    }

    private function queueDynamicOptions(Session $session, ?int $parentNodeId): int
    {
        $db = Database::getConnection();

        // Reconstrueer effectieve history door SELECT/BACK events te replayen
        // zodat teruggedraaide keuzes niet in de AI-context verschijnen.
        $stmt = $db->prepare(
            "SELECT event_type, label FROM session_events
             WHERE session_id = ? AND event_type IN ('SELECT','BACK') ORDER BY ts ASC"
        );
        $stmt->execute([$session->id]);
        $history = [];
        foreach ($stmt->fetchAll() as $ev) {
            if ($ev['event_type'] === 'SELECT') {
                $history[] = $ev['label'];
            } elseif (!empty($history)) {
                array_pop($history);
            }
        }

        // Labels die al getoond zijn (herhaling voorkomen)
        $stmt2 = $db->prepare(
            "SELECT DISTINCT label FROM session_events WHERE session_id = ? AND event_type = 'SHOWN'"
        );
        $stmt2->execute([$session->id]);
        $shownOptions = array_column($stmt2->fetchAll(), 'label');

        // Huidige intentie-hypothese uit session meta (voor betere AI-gissing)
        $metaStmt = $db->prepare("SELECT meta_json FROM sessions WHERE id = ?");
        $metaStmt->execute([$session->id]);
        $metaRow      = $metaStmt->fetch();
        $meta         = ($metaRow && $metaRow['meta_json']) ? (json_decode($metaRow['meta_json'], true) ?? []) : [];
        $currentState = $meta['current_state'] ?? null;

        $params = json_encode([
            'session_id'     => $session->id,
            'tree_id'        => $session->treeId,
            'parent_node_id' => $parentNodeId,
            'history'        => $history,
            'shown_options'  => $shownOptions,
            'current_state'  => $currentState,
            'profile_id'     => $session->profileId,
            'language'       => 'nl',
        ]);

        $db->prepare(
            "INSERT INTO ai_jobs (job_type, topic, params_json, status)
             VALUES ('dynamic_options', 'dynamic', ?, 'pending')"
        )->execute([$params]);

        return (int)$db->lastInsertId();
    }
}

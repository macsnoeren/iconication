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
            $jobId = $this->queueDynamicOptions($session, $parentNodeId);
            return OptionsResult::pending($jobId);
        }

        return OptionsResult::immediate($this->getStaticOptions($session, $parentNodeId));
    }

    private function getStaticOptions(Session $session, ?int $parentNodeId): array
    {
        $candidates = $this->trees->getChildren($session->treeId, $parentNodeId);

        foreach ($candidates as &$node) {
            $node['score'] = $this->score($node, $session);
        }
        unset($node);

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        $limit = $this->limitForProfile($session->profileId);
        return array_slice($candidates, 0, $limit);
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

        // Haal selectie-geschiedenis op voor context
        $stmt = $db->prepare(
            "SELECT label FROM session_events
             WHERE session_id = ? AND event_type = 'SELECT' ORDER BY ts ASC"
        );
        $stmt->execute([$session->id]);
        $history = array_column($stmt->fetchAll(), 'label');

        $params = json_encode([
            'session_id'     => $session->id,
            'tree_id'        => $session->treeId,
            'parent_node_id' => $parentNodeId,
            'history'        => $history,
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

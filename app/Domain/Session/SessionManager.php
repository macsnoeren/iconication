<?php
declare(strict_types=1);

namespace App\Domain\Session;

use App\Core\Database;
use App\Domain\Content\TreeRepository;
use App\Domain\Intent\IntentEngine;

class SessionManager
{
    public function __construct(
        private TreeRepository $trees,
        private IntentEngine   $intent,
    ) {}

    public function start(int $profileId, int $forcedTreeId = 0): Session
    {
        $db     = Database::getConnection();
        $treeId = $forcedTreeId > 0 ? $forcedTreeId : $this->trees->getBestTreeForProfile($profileId);
        $id     = bin2hex(random_bytes(16));

        $db->prepare(
            "INSERT INTO sessions (id, profile_id, tree_id, state) VALUES (?, ?, ?, 'EXPLORING')"
        )->execute([$id, $profileId, $treeId]);

        $this->logEvent($id, 'START');
        return new Session($id, $profileId, $treeId, 'EXPLORING', date('Y-m-d H:i:s'));
    }

    public function select(string $sessionId, int $nodeId): SelectResult
    {
        $session = $this->load($sessionId);
        $node    = $this->trees->getNode($nodeId);

        $this->logEvent($sessionId, 'SELECT', $nodeId, $node['label']);

        if ($node['is_leaf']) {
            $session->state = 'CONFIRMING';
            $this->updateState($sessionId, 'CONFIRMING');
            return new SelectResult(
                options:          \App\Domain\Intent\OptionsResult::immediate([]),
                sentence:         $this->buildSentence($sessionId),
                newState:         'CONFIRMING',
                suggestedMessage: $node['suggested_message'],
            );
        }

        $navId   = !empty($node['next_node_id']) ? (int)$node['next_node_id'] : $nodeId;
        $options = $this->intent->getNextOptions($session, $navId);
        return new SelectResult(
            options:          $options,
            sentence:         $this->buildSentence($sessionId),
            newState:         'EXPLORING',
            suggestedMessage: null,
        );
    }

    public function back(string $sessionId): BackResult
    {
        $session = $this->load($sessionId);
        $this->logEvent($sessionId, 'BACK');

        if ($session->state === 'CONFIRMING') {
            $this->updateState($sessionId, 'EXPLORING');
            $session->state = 'EXPLORING';
        }

        // Bouw history opnieuw op zonder de laatste SELECT
        $selects = $this->getSelectHistory($sessionId);
        array_pop($selects);

        $parentNodeId = empty($selects) ? null : (int)end($selects)['node_id'];
        $options      = $this->intent->getNextOptions($session, $parentNodeId);

        return new BackResult($options, $this->buildSentenceFromHistory($selects));
    }

    public function complete(string $sessionId): void
    {
        $this->logEvent($sessionId, 'COMPLETE');
        $this->updateState($sessionId, 'COMPLETED');
        $this->queueWeightUpdate($sessionId);
    }

    public function reject(string $sessionId): BackResult
    {
        return $this->back($sessionId);
    }

    public function restore(string $sessionId): ?RestoreResult
    {
        $session = $this->load($sessionId);
        if (!$session->isActive()) return null;

        $selects      = $this->getSelectHistory($sessionId);
        $lastNodeId   = empty($selects) ? null : (int)end($selects)['node_id'];
        $parentNodeId = $lastNodeId !== null ? $this->trees->getParentId($lastNodeId) : null;
        $options      = $this->intent->getNextOptions($session, $parentNodeId);

        return new RestoreResult($session, $options, $this->buildSentenceFromHistory($selects));
    }

    // Verwerkt het resultaat van een dynamic_options AI-job en slaat de
    // gegenereerde nodes op zodat de sessie verder kan.
    public function applyDynamicResult(string $sessionId, array $aiOptions, array $currentState = []): SelectResult
    {
        $session  = $this->load($sessionId);
        $selects  = $this->getSelectHistory($sessionId);
        $depth    = count($selects);
        $parentId = empty($selects) ? null : (int)end($selects)['node_id'];

        $this->trees->insertDynamicNodes($session->treeId, $parentId, $depth, $aiOptions);

        $rawNodes = $this->trees->getChildren($session->treeId, $parentId);

        $this->logShownOptions($sessionId, $rawNodes);

        if (!empty($currentState)) {
            $meta = $this->getSessionMeta($sessionId);
            $meta['current_state'] = $currentState;
            $this->setSessionMeta($sessionId, $meta);
        }

        return new SelectResult(
            options:          \App\Domain\Intent\OptionsResult::immediate($rawNodes),
            sentence:         $this->buildSentenceFromHistory($selects),
            newState:         'EXPLORING',
            suggestedMessage: null,
        );
    }

    public function getSentence(string $sessionId): string
    {
        return $this->buildSentence($sessionId);
    }

    public function setConfirming(string $sessionId): void
    {
        $this->updateState($sessionId, 'CONFIRMING');
    }

    public function getSessionMeta(string $sessionId): array
    {
        $stmt = Database::getConnection()->prepare("SELECT meta_json FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row && $row['meta_json'] ? (json_decode($row['meta_json'], true) ?? []) : [];
    }

    public function setSessionMeta(string $sessionId, array $meta): void
    {
        Database::getConnection()->prepare(
            "UPDATE sessions SET meta_json = ? WHERE id = ?"
        )->execute([json_encode($meta), $sessionId]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function load(string $sessionId): Session
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        if (!$row) throw new \RuntimeException("Session $sessionId not found");
        return new Session($row['id'], (int)$row['profile_id'], (int)$row['tree_id'], $row['state'], $row['started_at']);
    }

    private function logEvent(string $sessionId, string $type, ?int $nodeId = null, ?string $label = null): void
    {
        Database::getConnection()->prepare(
            "INSERT INTO session_events (session_id, event_type, node_id, label) VALUES (?, ?, ?, ?)"
        )->execute([$sessionId, $type, $nodeId, $label]);
    }

    private function updateState(string $sessionId, string $state): void
    {
        Database::getConnection()->prepare(
            "UPDATE sessions SET state = ?, last_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$state, $sessionId]);
    }

    private function getSelectHistory(string $sessionId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT node_id, label FROM session_events
             WHERE session_id = ? AND event_type = 'SELECT' ORDER BY ts ASC"
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    private function buildSentence(string $sessionId): string
    {
        return $this->buildSentenceFromHistory($this->getSelectHistory($sessionId));
    }

    private function buildSentenceFromHistory(array $selects): string
    {
        $labels = array_column($selects, 'label');
        return implode(' → ', $labels);
    }

    private function logShownOptions(string $sessionId, array $nodes): void
    {
        foreach ($nodes as $node) {
            if (!empty($node['label'])) {
                $this->logEvent($sessionId, 'SHOWN', null, $node['label']);
            }
        }
    }

    private function getShownOptions(string $sessionId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT DISTINCT label FROM session_events
             WHERE session_id = ? AND event_type = 'SHOWN'"
        );
        $stmt->execute([$sessionId]);
        return array_column($stmt->fetchAll(), 'label');
    }

    private function queueWeightUpdate(string $sessionId): void
    {
        Database::getConnection()->prepare(
            "INSERT INTO ai_jobs (job_type, topic, params_json, status)
             VALUES ('update_weights', 'system', ?, 'pending')"
        )->execute([json_encode(['session_id' => $sessionId])]);
    }
}

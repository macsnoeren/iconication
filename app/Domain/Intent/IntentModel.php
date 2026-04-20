<?php
declare(strict_types=1);

namespace App\Domain\Intent;

use App\Core\Database;

class IntentModel
{
    public function getWeight(int $profileId, int $nodeId): float
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT weight FROM intent_weights WHERE profile_id = ? AND node_id = ?"
        );
        $stmt->execute([$profileId, $nodeId]);
        $row = $stmt->fetch();
        return $row ? (float)$row['weight'] : 0.5;
    }

    public function recentlySelected(int $profileId, int $nodeId, int $days = 7): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT 1 FROM session_events se
             JOIN sessions s ON s.id = se.session_id
             WHERE s.profile_id = ?
               AND se.node_id = ?
               AND se.event_type = 'SELECT'
               AND se.ts >= datetime('now', '-' || ? || ' days')
             LIMIT 1"
        );
        $stmt->execute([$profileId, $nodeId, $days]);
        return (bool)$stmt->fetch();
    }

    // Wordt aangeroepen door Python worker (update_weights job).
    // Voor PHP-side leren na sessie-afsluiting: eenvoudige frequentie-update.
    public function bumpWeight(int $profileId, int $nodeId, float $delta = 0.05): void
    {
        $db      = Database::getConnection();
        $current = $this->getWeight($profileId, $nodeId);
        $new     = min(0.95, max(0.1, $current + $delta));

        $db->prepare(
            "INSERT INTO intent_weights (profile_id, node_id, weight, updated_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT(profile_id, node_id) DO UPDATE
             SET weight = excluded.weight, updated_at = excluded.updated_at"
        )->execute([$profileId, $nodeId, $new]);
    }
}

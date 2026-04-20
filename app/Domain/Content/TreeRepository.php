<?php
declare(strict_types=1);

namespace App\Domain\Content;

use App\Core\Database;

class TreeRepository
{
    public function getChildren(int $treeId, ?int $parentNodeId): array
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order
             FROM tree_nodes
             WHERE tree_id = ? AND parent_id IS ?
             ORDER BY sort_order ASC"
        );
        $stmt->execute([$treeId, $parentNodeId]);
        return $stmt->fetchAll();
    }

    public function getNode(int $nodeId): array
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message
             FROM tree_nodes WHERE id = ?"
        );
        $stmt->execute([$nodeId]);
        $row = $stmt->fetch();
        if (!$row) throw new \RuntimeException("tree_node $nodeId not found");
        return $row;
    }

    public function getParentId(int $nodeId): ?int
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare("SELECT parent_id FROM tree_nodes WHERE id = ?");
        $stmt->execute([$nodeId]);
        $row = $stmt->fetch();
        return $row ? ($row['parent_id'] !== null ? (int)$row['parent_id'] : null) : null;
    }

    // Geeft de beste (meest recente 'ready') boom terug voor een profiel.
    // Bij generation_mode='dynamic' bestaat er geen pre-gegenereerde boom;
    // we geven dan tree_id=0 terug zodat de IntentEngine weet dat AI nodig is.
    public function getBestTreeForProfile(int $profileId): int
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id FROM option_trees
             WHERE (profile_id = ? OR profile_id IS NULL)
               AND status = 'ready'
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$profileId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : 0;
    }

    public function getGenerationMode(int $treeId): string
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare("SELECT generation_mode FROM option_trees WHERE id = ?");
        $stmt->execute([$treeId]);
        $row = $stmt->fetch();
        return $row ? (string)$row['generation_mode'] : 'static';
    }

    public function getAllReady(): array
    {
        return Database::getConnection()
            ->query("SELECT * FROM option_trees WHERE status = 'ready' ORDER BY name ASC")
            ->fetchAll();
    }

    // Sla door AI gegenereerde nodes op voor een dynamische sessie-stap.
    // Geeft de ingevoegde node-IDs terug.
    public function insertDynamicNodes(int $treeId, ?int $parentId, int $depth, array $options): array
    {
        $db  = Database::getConnection();

        // Verwijder bestaande kinderen op dit niveau zodat "Iets anders" geen
        // opeenstapeling van oude + nieuwe opties geeft.
        $db->prepare(
            "DELETE FROM tree_nodes WHERE tree_id = ? AND " .
            ($parentId === null ? "parent_id IS NULL" : "parent_id = ?")
        )->execute($parentId === null ? [$treeId] : [$treeId, $parentId]);

        $ids = [];
        foreach ($options as $i => $opt) {
            $stmt = $db->prepare(
                "INSERT INTO tree_nodes (tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $treeId,
                $parentId,
                $depth,
                $opt['label'],
                $opt['image_url'] ?? null,
                $opt['is_leaf']   ?? 0,
                $opt['suggested_message'] ?? null,
                $i,
            ]);
            $ids[] = (int)$db->lastInsertId();
        }
        return $ids;
    }
}

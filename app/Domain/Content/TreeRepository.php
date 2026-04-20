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
            "SELECT id, tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order, next_node_id
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
            "SELECT id, tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, next_node_id
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

    // Kopieert een bestaande boom als nieuwe statische boom.
    // Parent-ID's worden opnieuw gemapped zodat de structuur intact blijft.
    public function copyAsStaticTree(int $sourceTreeId, string $name): int
    {
        $db = Database::getConnection();

        $db->prepare(
            "INSERT INTO option_trees (profile_id, name, language, status, generation_mode)
             SELECT profile_id, ?, language, 'ready', 'static' FROM option_trees WHERE id = ?"
        )->execute([$name, $sourceTreeId]);
        $newTreeId = (int)$db->lastInsertId();

        $stmt = $db->prepare(
            "SELECT * FROM tree_nodes WHERE tree_id = ? ORDER BY depth ASC, id ASC"
        );
        $stmt->execute([$sourceTreeId]);
        $nodes = $stmt->fetchAll();

        $idMap = [];
        foreach ($nodes as $node) {
            $newParentId = $node['parent_id'] === null ? null : ($idMap[$node['parent_id']] ?? null);
            $db->prepare(
                "INSERT INTO tree_nodes (tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $newTreeId,
                $newParentId,
                $node['depth'],
                $node['label'],
                $node['image_url'],
                $node['is_leaf'],
                $node['suggested_message'],
                $node['sort_order'],
            ]);
            $idMap[$node['id']] = (int)$db->lastInsertId();
        }

        return $newTreeId;
    }

    // Sla door AI gegenereerde nodes op voor een dynamische sessie-stap.
    // Voegt ook altijd een "Iets anders" sibling toe zodat de boom intact blijft.
    public function insertDynamicNodes(int $treeId, ?int $parentId, int $depth, array $options): array
    {
        $db  = Database::getConnection();
        $ids = [];

        foreach ($options as $i => $opt) {
            $label    = $opt['label'];
            $imageUrl = $opt['image_url'] ?? null;

            // Gebruik bestaand plaatje als AI er geen heeft gevonden
            if (!$imageUrl) {
                $imageUrl = $this->lookupImageForLabel($label);
            }

            $db->prepare(
                "INSERT INTO tree_nodes (tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $treeId, $parentId, $depth,
                $label, $imageUrl,
                $opt['is_leaf']   ?? 0,
                $opt['suggested_message'] ?? null,
                $i,
            ]);
            $ids[] = (int)$db->lastInsertId();

            // Propageer een nieuw plaatje naar alle bestaande nodes met hetzelfde label
            if ($imageUrl) {
                $this->syncImageForLabel($label, $imageUrl);
            }
        }

        // "Iets anders" als vaste laatste node zodat de boom volledig doorloopbaar blijft.
        $db->prepare(
            "INSERT INTO tree_nodes (tree_id, parent_id, depth, label, image_url, is_leaf, suggested_message, sort_order)
             VALUES (?, ?, ?, 'Iets anders', NULL, 0, NULL, ?)"
        )->execute([$treeId, $parentId, $depth, count($options)]);

        return $ids;
    }

    // Zet hetzelfde plaatje op alle nodes met dit label (case-insensitief).
    public function syncImageForLabel(string $label, string $imageUrl): void
    {
        Database::getConnection()->prepare(
            "UPDATE tree_nodes SET image_url = ?
             WHERE LOWER(label) = LOWER(?) AND (image_url IS NULL OR image_url = '' OR image_url != ?)"
        )->execute([$imageUrl, $label, $imageUrl]);
    }

    // Zoek een bestaand plaatje voor dit label in de hele database.
    public function lookupImageForLabel(string $label): ?string
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT image_url FROM tree_nodes
             WHERE LOWER(label) = LOWER(?) AND image_url IS NOT NULL AND image_url != ''
             LIMIT 1"
        );
        $stmt->execute([$label]);
        return $stmt->fetchColumn() ?: null;
    }
}

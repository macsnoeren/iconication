<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class AdminController {
    private \PDO $db;

    public function __construct() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: " . BASE_URL . "?action=login");
            exit;
        }
        $this->db = Database::getConnection();
    }

    public function index(): void {
        $topics = $this->db->query("SELECT * FROM topics")->fetchAll();
        $view = 'admin_dashboard';
        include __DIR__ . "/../../views/layout.php";
    }

    public function deleteTopic(int $id): void {
        // Verwijder eerst alle nodes en opties die bij dit topic horen
        $stmtNodes = $this->db->prepare("SELECT id FROM nodes WHERE topic_id = ?");
        $stmtNodes->execute([$id]);
        $nodeIds = $stmtNodes->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($nodeIds)) {
            $placeholders = implode(',', array_fill(0, count($nodeIds), '?'));
            $this->db->prepare("DELETE FROM options WHERE node_id IN ($placeholders)")->execute($nodeIds);
            $this->db->prepare("DELETE FROM nodes WHERE id IN ($placeholders)")->execute($nodeIds);
        }

        $stmt = $this->db->prepare("DELETE FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    public function addTopic(): void {
        $view = 'admin_topic_form';
        $topic = ['id' => null, 'name' => '', 'root_node_id' => null];
        include __DIR__ . "/../../views/layout.php";
    }

    public function saveTopic(?int $topicId = null): void {
        $name = $_POST['name'] ?? '';
        $rootNodeId = $_POST['root_node_id'] ?? null;

        if (empty($name)) {
            // Error handling
            header("Location: " . BASE_URL . "?action=admin"); // Redirect back for now
            exit;
        }

        if ($topicId) {
            $stmt = $this->db->prepare("UPDATE topics SET name = ?, root_node_id = ? WHERE id = ?");
            $stmt->execute([$name, $rootNodeId, $topicId]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO topics (name, root_node_id) VALUES (?, ?)");
            $stmt->execute([$name, $rootNodeId]);
        }

        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    public function showTopicNodes(int $topicId): void {
        $stmtTopic = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmtTopic->execute([$topicId]);
        $topic = $stmtTopic->fetch();

        $stmtNodes = $this->db->prepare("SELECT * FROM nodes WHERE topic_id = ?");
        $stmtNodes->execute([$topicId]);
        $nodes = $stmtNodes->fetchAll();

        $view = 'admin_topic_nodes';
        include __DIR__ . "/../../views/layout.php";
    }

    public function addNode(int $topicId): void {
        $node = ['id' => null, 'topic_id' => $topicId];
        $options = [
            ['label' => '', 'image_url' => '', 'next_node_id' => null],
            ['label' => '', 'image_url' => '', 'next_node_id' => null]
        ];
        $view = 'admin_node_form';
        include __DIR__ . "/../../views/layout.php";
    }

    public function editNode(int $topicId, int $nodeId): void {
        $stmtNode = $this->db->prepare("SELECT * FROM nodes WHERE id = ? AND topic_id = ?");
        $stmtNode->execute([$nodeId, $topicId]);
        $node = $stmtNode->fetch();

        if (!$node) {
            header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
            exit;
        }

        $stmtOptions = $this->db->prepare("SELECT * FROM options WHERE node_id = ? ORDER BY id ASC");
        $stmtOptions->execute([$nodeId]);
        $options = $stmtOptions->fetchAll();

        // Zorg dat er altijd 2 opties zijn voor het formulier
        while (count($options) < 2) {
            $options[] = ['id' => null, 'label' => '', 'image_url' => '', 'next_node_id' => null];
        }

        $view = 'admin_node_form';
        include __DIR__ . "/../../views/layout.php";
    }

    public function saveNode(int $topicId, ?int $nodeId = null): void {
        $optionLabels = $_POST['option_label'] ?? [];
        $optionImages = $_POST['option_image'] ?? [];
        $optionNextNodes = $_POST['option_next_node'] ?? [];
        $optionIds = $_POST['option_id'] ?? [];

        if ($nodeId) {
            // Update bestaande node (alleen topic_id is relevant hier)
            $stmt = $this->db->prepare("UPDATE nodes SET topic_id = ? WHERE id = ?");
            $stmt->execute([$topicId, $nodeId]);
        } else {
            // Nieuwe node aanmaken
            $stmt = $this->db->prepare("INSERT INTO nodes (topic_id) VALUES (?)");
            $stmt->execute([$topicId]);
            $nodeId = (int)$this->db->lastInsertId();
        }

        // Opties opslaan
        for ($i = 0; $i < 2; $i++) { // We verwachten altijd 2 opties
            $label = $optionLabels[$i] ?? '';
            $image = $optionImages[$i] ?? '';
            $nextNode = $optionNextNodes[$i] ?? null;
            $optionId = $optionIds[$i] ?? null;

            if ($optionId) {
                $stmt = $this->db->prepare("UPDATE options SET label = ?, image_url = ?, next_node_id = ? WHERE id = ?");
                $stmt->execute([$label, $image, $nextNode, $optionId]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nodeId, $label, $image, $nextNode]);
            }
        }

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
        exit;
    }

    public function deleteNode(int $topicId, int $nodeId): void {
        // Verwijder eerst de opties van de node
        $stmtOptions = $this->db->prepare("DELETE FROM options WHERE node_id = ?");
        $stmtOptions->execute([$nodeId]);

        // Verwijder dan de node zelf
        $stmtNode = $this->db->prepare("DELETE FROM nodes WHERE id = ?");
        $stmtNode->execute([$nodeId]);

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
        exit;
    }
}
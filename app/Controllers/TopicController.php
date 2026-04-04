<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class TopicController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function index(): void {
        $stmt = $this->db->query("SELECT * FROM topics");
        $topics = $stmt->fetchAll();
        $this->render('home', ['topics' => $topics]);
    }

    public function start(int $id): void {
        $stmt = $this->db->prepare("SELECT root_node_id FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        $rootId = $stmt->fetchColumn();
        
        $_SESSION['history'] = [];
        header("Location: " . BASE_PATH . "/topic/$id/node/$rootId");
        exit;
    }

    public function showNode(int $topicId, int $nodeId): void {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        $stmt = $this->db->prepare("SELECT * FROM options WHERE node_id = ?");
        $stmt->execute([$nodeId]);
        $options = $stmt->fetchAll();

        // History management
        if (!isset($_SESSION['history'])) $_SESSION['history'] = [];
        if (empty($_SESSION['history']) || end($_SESSION['history']) !== $nodeId) {
            $_SESSION['history'][] = $nodeId;
        }

        $this->render('node', [
            'topic' => $topic,
            'options' => $options,
            'topicId' => $topicId
        ]);
    }

    public function back(int $topicId): void {
        if (isset($_SESSION['history']) && count($_SESSION['history']) > 1) {
            array_pop($_SESSION['history']); 
            $prev = array_pop($_SESSION['history']);
            header("Location: " . BASE_PATH . "/topic/$topicId/node/$prev");
        } else {
            header("Location: " . BASE_PATH . "/");
        }
        exit;
    }

    public function reset(int $topicId): void {
        header("Location: " . BASE_PATH . "/topic/$topicId");
        exit;
    }

    private function render(string $view, array $data): void {
        extract($data);
        include __DIR__ . "/../../views/layout.php";
    }
}
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
        $stmt = $this->db->prepare("DELETE FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }
}
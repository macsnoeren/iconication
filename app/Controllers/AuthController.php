<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class AuthController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function login(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: " . BASE_URL . "?action=admin");
                exit;
            }
            $error = "Ongeldige inloggegevens.";
        }

        $view = 'login';
        include __DIR__ . "/../../views/layout.php";
    }

    public function logout(): void {
        session_destroy();
        header("Location: " . BASE_URL);
        exit;
    }
}
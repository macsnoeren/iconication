<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class AuthController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function setup(): void {
        $userCount = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($userCount > 0) {
            header("Location: " . BASE_URL . "?action=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($username && $password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$username, $hashed]);
                
                header("Location: " . BASE_URL . "?action=login");
                exit;
            }
        }

        $view = 'setup';
        include __DIR__ . "/../../views/layout.php";
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

    public function changePassword(): void {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "?action=login");
            exit;
        }

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $error = "Nieuwe wachtwoorden komen niet overeen.";
            } else {
                $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user && password_verify($currentPassword, $user['password'])) {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$hashed, $_SESSION['user_id']]);
                    $success = "Wachtwoord succesvol gewijzigd.";
                } else {
                    $error = "Huidig wachtwoord is onjuist.";
                }
            }
        }

        $view = 'change_password';
        include __DIR__ . "/../../views/layout.php";
    }
}
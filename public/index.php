<?php
declare(strict_types=1);
session_start();

// 1. PSR-4 Autoloader (zonder Composer)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    // Vervang backslashes door forward slashes voor Linux servers
    $relative_class_path = str_replace('\\', '/', $relative_class);
    $file = $base_dir . $relative_class_path . '.php';
    
    if (file_exists($file)) require $file;
});

// 2. Simpele Router
$scriptName = $_SERVER['SCRIPT_NAME']; // /iconication/public/index.php
$scriptPath = str_replace('\\', '/', dirname($scriptName)); // /iconication/public
define('BASE_URL', $scriptName); // /iconication/public/index.php

use App\Controllers\TopicController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
$controller = new TopicController();

// Routing op basis van GET variabelen
$topicId = isset($_GET['topic']) ? (int)$_GET['topic'] : null;
$nodeId = isset($_GET['node']) ? (int)$_GET['node'] : null;
$action = $_GET['action'] ?? null;

// Check of er users zijn. Zo niet, forceer setup.
$db = \App\Core\Database::getConnection();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

if ($userCount == 0 && $action !== 'setup') {
    header("Location: " . BASE_URL . "?action=setup");
    exit;
}

if ($action === 'back' && $topicId) {
    $controller->back($topicId);
} elseif ($action === 'reset' && $topicId) {
    $controller->reset($topicId);
} elseif ($topicId && $nodeId) {
    $controller->showNode($topicId, $nodeId);
} elseif ($action === 'setup') {
    (new AuthController())->setup();
} elseif ($action === 'login') {
    (new AuthController())->login();
} elseif ($action === 'logout') {
    (new AuthController())->logout();
} elseif ($action === 'change_password') {
    (new AuthController())->changePassword();
} elseif (strpos($action ?? '', 'admin') === 0) {
    $admin = new AdminController();
    if ($action === 'admin') $admin->index();
    if ($action === 'admin_topic_delete' && $topicId) $admin->deleteTopic($topicId);
} elseif ($topicId) {
    $controller->start($topicId);
} elseif (empty($_GET)) {
    $controller->index();
} else {
    http_response_code(404);
    echo "<h1>404 - Pagina niet gevonden</h1>";
    echo "<a href='" . BASE_URL . "'>Ga terug naar start</a>";
}
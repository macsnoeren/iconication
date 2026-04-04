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
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Zorg dat de router werkt, ook als de app in een submap staat
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$uri = substr($requestUri, strlen($scriptPath));

// Definieer de BASE_PATH voor gebruik in links en redirects
$basePath = ($scriptPath === '/') ? '' : $scriptPath;
define('BASE_PATH', $basePath);

// Normaliseer de URI: verwijder trailing slashes en zorg voor een slash aan het begin
$uri = '/' . trim($uri, '/');

use App\Controllers\TopicController;

$controller = new TopicController();

if ($uri === '/' || $uri === '/index.php') {
    $controller->index();
} elseif (preg_match('#^/topic/(\d+)$#', $uri, $matches)) {
    $controller->start((int)$matches[1]);
} elseif (preg_match('#^/topic/(\d+)/node/(\d+)$#', $uri, $matches)) {
    $controller->showNode((int)$matches[1], (int)$matches[2]);
} elseif (preg_match('#^/topic/(\d+)/back$#', $uri, $matches)) {
    $controller->back((int)$matches[1]);
} elseif (preg_match('#^/topic/(\d+)/reset$#', $uri, $matches)) {
    $controller->reset((int)$matches[1]);
} else {
    http_response_code(404);
    echo "<h1>404 - Pagina niet gevonden</h1>";
    echo "<a href='" . BASE_PATH . "/'>Ga terug naar start</a>";
}
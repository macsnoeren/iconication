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
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// 2. Simpele Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

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
    echo "<a href='/'>Ga terug naar start</a>";
}
<?php
declare(strict_types=1);
session_start();

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . $relative . '.php';
    if (file_exists($file)) require $file;
});

$scriptName = $_SERVER['SCRIPT_NAME'];
define('BASE_URL', $scriptName);

$action = $_GET['action'] ?? null;

// Zorg dat er users zijn
$db        = \App\Core\Database::getConnection();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount == 0 && $action !== 'setup') {
    header("Location: " . BASE_URL . "?action=setup"); exit;
}

// ── Interactie (nieuwe architectuur) ────────────────────────────────────────
$interaction = new \App\Controllers\InteractionController();

$sessionRoutes = [
    'session_start'          => fn() => $interaction->start(),
    'session_select'         => fn() => $interaction->select(),
    'session_back'           => fn() => $interaction->back(),
    'session_confirm'        => fn() => $interaction->confirm(),
    'session_reject'         => fn() => $interaction->reject(),
    'session_dynamic_status' => fn() => $interaction->dynamicStatus(),
    'session_show'           => fn() => $interaction->show(),
];

if (isset($sessionRoutes[$action])) {
    ($sessionRoutes[$action])();
    exit;
}

// ── Auth ─────────────────────────────────────────────────────────────────────
$auth = new \App\Controllers\AuthController();
$authRoutes = [
    'setup'           => fn() => $auth->setup(),
    'login'           => fn() => $auth->login(),
    'logout'          => fn() => $auth->logout(),
    'change_password' => fn() => $auth->changePassword(),
];

if (isset($authRoutes[$action])) {
    ($authRoutes[$action])();
    exit;
}

// ── Admin ────────────────────────────────────────────────────────────────────
if (str_starts_with($action ?? '', 'admin') || $action === 'admin_regenerate_discovery') {
    $topicId = isset($_GET['topic']) ? (int)$_GET['topic'] : null;
    $nodeId  = isset($_GET['node'])  ? (int)$_GET['node']  : null;
    $admin   = new \App\Controllers\AdminController();

    match($action) {
        'admin'                   => $admin->index(),
        'admin_topic_delete'      => $topicId ? $admin->deleteTopic($topicId)     : $admin->index(),
        'admin_add_topic'         => $admin->addTopic(),
        'admin_save_topic'        => $admin->saveTopic($topicId),
        'admin_topic_nodes'       => $topicId ? $admin->showTopicNodes($topicId)  : $admin->index(),
        'admin_add_node'          => $topicId ? $admin->addNode($topicId)         : $admin->index(),
        'admin_edit_node'         => ($topicId && $nodeId) ? $admin->editNode($topicId, $nodeId) : $admin->index(),
        'admin_save_node'         => $topicId ? $admin->saveNode($topicId, $nodeId) : $admin->index(),
        'admin_delete_node'       => ($topicId && $nodeId) ? $admin->deleteNode($topicId, $nodeId) : $admin->index(),
        'admin_get_images'        => $admin->getExistingImages(),
        'admin_training'          => $admin->showTraining(),
        'admin_save_training'     => $admin->saveTraining(),
        'admin_topic_edit'        => $topicId ? $admin->showTopicEdit($topicId)   : $admin->index(),
        'admin_save_topic_edit'   => $topicId ? $admin->saveTopicEdit($topicId)   : $admin->index(),
        'admin_ai_generate'       => $admin->aiGenerateTopic(),
        'admin_ai_queue'          => $admin->aiQueueTopic(),
        'admin_ai_waiting'        => isset($_GET['job']) ? $admin->aiWaiting((int)$_GET['job']) : $admin->index(),
        'admin_ai_job_status'     => isset($_GET['job']) ? $admin->aiJobStatus((int)$_GET['job']) : $admin->index(),
        'admin_ai_preview'        => isset($_GET['job']) ? $admin->aiPreviewJob((int)$_GET['job']) : $admin->index(),
        'admin_ai_save'           => $admin->aiSaveTopic(),
        'admin_regenerate_discovery' => (function() use ($db) {
            header('Content-Type: application/json');
            $discPath = dirname(__DIR__) . '/storage/discovery_tree.json';
            if (file_exists($discPath)) unlink($discPath);
            $stmt = $db->prepare("INSERT INTO ai_jobs (topic, goal, job_type) VALUES ('Ontdekking', 'Genereer ontdekkers-boom', 'discovery')");
            $stmt->execute();
            $jobId = (int)$db->lastInsertId();
            echo json_encode(['redirect' => BASE_URL . "?action=discovery_waiting&job=$jobId"]);
            exit;
        })(),
        default => (function() { header("Location: " . BASE_URL . "?action=admin"); exit; })(),
    };
    exit;
}

// ── Worker API ───────────────────────────────────────────────────────────────
$api = new \App\Controllers\ApiController();
$apiRoutes = [
    'api_pending_jobs'       => fn() => $api->pendingJobs(),
    'api_submit_result'      => fn() => $api->submitResult(),
    'api_training_examples'  => fn() => $api->trainingExamples(),
];

if (isset($apiRoutes[$action])) {
    ($apiRoutes[$action])();
    exit;
}

// ── Fallback: start nieuwe sessie ────────────────────────────────────────────
$interaction->restore();

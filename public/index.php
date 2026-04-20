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

// ── Worker API — altijd eerst, vóór andere controllers ───────────────────────
// ApiController-constructor exitst meteen met 401 als Bearer-token ontbreekt,
// dus we instantiëren hem uitsluitend voor api_*-routes.
if (in_array($action, ['api_pending_jobs', 'api_submit_result', 'api_training_examples'], true)) {
    $api = new \App\Controllers\ApiController();
    match($action) {
        'api_pending_jobs'      => $api->pendingJobs(),
        'api_submit_result'     => $api->submitResult(),
        'api_training_examples' => $api->trainingExamples(),
    };
    exit;
}

// ── Auth ──────────────────────────────────────────────────────────────────────
if (in_array($action, ['setup', 'login', 'logout', 'change_password'], true)) {
    $auth = new \App\Controllers\AuthController();
    match($action) {
        'setup'           => $auth->setup(),
        'login'           => $auth->login(),
        'logout'          => $auth->logout(),
        'change_password' => $auth->changePassword(),
    };
    exit;
}

// ── Admin ─────────────────────────────────────────────────────────────────────
if (str_starts_with($action ?? '', 'admin') || $action === 'admin_regenerate_discovery') {
    $topicId = isset($_GET['topic']) ? (int)$_GET['topic'] : null;
    $nodeId  = isset($_GET['node'])  ? (int)$_GET['node']  : null;
    $admin   = new \App\Controllers\AdminController();

    match($action) {
        'admin'                 => $admin->index(),
        'admin_topic_delete'    => $topicId ? $admin->deleteTopic($topicId)       : $admin->index(),
        'admin_add_topic'       => $admin->addTopic(),
        'admin_save_topic'      => $admin->saveTopic($topicId),
        'admin_topic_nodes'     => $topicId ? $admin->showTopicNodes($topicId)    : $admin->index(),
        'admin_add_node'        => $topicId ? $admin->addNode($topicId)           : $admin->index(),
        'admin_edit_node'       => ($topicId && $nodeId) ? $admin->editNode($topicId, $nodeId) : $admin->index(),
        'admin_save_node'       => $topicId ? $admin->saveNode($topicId, $nodeId) : $admin->index(),
        'admin_delete_node'     => ($topicId && $nodeId) ? $admin->deleteNode($topicId, $nodeId) : $admin->index(),
        'admin_get_images'      => $admin->getExistingImages(),
        'admin_training'        => $admin->showTraining(),
        'admin_save_training'   => $admin->saveTraining(),
        'admin_topic_edit'      => $topicId ? $admin->showTopicEdit($topicId)     : $admin->index(),
        'admin_save_topic_edit' => $topicId ? $admin->saveTopicEdit($topicId)     : $admin->index(),
        'admin_ai_generate'     => $admin->aiGenerateTopic(),
        'admin_ai_queue'        => $admin->aiQueueTopic(),
        'admin_ai_waiting'      => isset($_GET['job']) ? $admin->aiWaiting((int)$_GET['job'])    : $admin->index(),
        'admin_ai_job_status'   => isset($_GET['job']) ? $admin->aiJobStatus((int)$_GET['job'])  : $admin->index(),
        'admin_ai_preview'      => isset($_GET['job']) ? $admin->aiPreviewJob((int)$_GET['job']) : $admin->index(),
        'admin_ai_save'         => $admin->aiSaveTopic(),
        'admin_regenerate_discovery' => (function() use ($db) {
            header('Content-Type: application/json');
            $stmt = $db->prepare("INSERT INTO ai_jobs (topic, goal, job_type) VALUES ('Ontdekking', 'Genereer', 'discovery')");
            $stmt->execute();
            echo json_encode(['ok' => true]);
            exit;
        })(),
        default => (function() { header("Location: " . BASE_URL . "?action=admin"); exit; })(),
    };
    exit;
}

// ── Interactie ────────────────────────────────────────────────────────────────
$interaction = new \App\Controllers\InteractionController();

match($action) {
    'session_start'          => $interaction->start(),
    'session_select'         => $interaction->select(),
    'session_back'           => $interaction->back(),
    'session_confirm'        => $interaction->confirm(),
    'session_reject'         => $interaction->reject(),
    'session_dynamic_status' => $interaction->dynamicStatus(),
    'session_show'           => $interaction->show(),
    default                  => $interaction->restore(),
};

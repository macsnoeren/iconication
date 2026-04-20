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
use App\Controllers\ApiController;
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
} elseif ($action === 'setup') {
    (new AuthController())->setup();
} elseif ($action === 'login') {
    (new AuthController())->login();
} elseif ($action === 'logout') {
    (new AuthController())->logout();
} elseif ($action === 'change_password') {
    (new AuthController())->changePassword();
} elseif (strpos($action ?? '', 'admin') === 0) { // Admin acties
    $admin = new AdminController();
    if ($action === 'admin') {
        $admin->index();
    } elseif ($action === 'admin_topic_delete' && $topicId) {
        $admin->deleteTopic($topicId);
    } elseif ($action === 'admin_add_topic') {
        $admin->addTopic();
    } elseif ($action === 'admin_save_topic') {
        $admin->saveTopic($topicId);
    } elseif ($action === 'admin_topic_nodes' && $topicId) {
        $admin->showTopicNodes($topicId);
    } elseif ($action === 'admin_add_node' && $topicId) {
        $admin->addNode($topicId);
    } elseif ($action === 'admin_edit_node' && $topicId && $nodeId) {
        $admin->editNode($topicId, $nodeId);
    } elseif ($action === 'admin_save_node' && $topicId) { // nodeId kan null zijn voor nieuwe node
        $admin->saveNode($topicId, $nodeId);
    } elseif ($action === 'admin_delete_node' && $topicId && $nodeId) {
        $admin->deleteNode($topicId, $nodeId);
    } elseif ($action === 'admin_get_images') {
        $admin->getExistingImages();
    } elseif ($action === 'admin_training') {
        $admin->showTraining();
    } elseif ($action === 'admin_save_training') {
        $admin->saveTraining();
    } elseif ($action === 'admin_topic_edit' && $topicId) {
        $admin->showTopicEdit($topicId);
    } elseif ($action === 'admin_save_topic_edit' && $topicId) {
        $admin->saveTopicEdit($topicId);
    } elseif ($action === 'admin_ai_generate') {
        $admin->aiGenerateTopic();
    } elseif ($action === 'admin_ai_queue') {
        $admin->aiQueueTopic();
    } elseif ($action === 'admin_ai_waiting' && isset($_GET['job'])) {
        $admin->aiWaiting((int)$_GET['job']);
    } elseif ($action === 'admin_ai_job_status' && isset($_GET['job'])) {
        $admin->aiJobStatus((int)$_GET['job']);
    } elseif ($action === 'admin_ai_preview' && isset($_GET['job'])) {
        $admin->aiPreviewJob((int)$_GET['job']);
    } elseif ($action === 'admin_ai_save') {
        $admin->aiSaveTopic();
    } else {
        // Als een admin actie is aangevraagd maar niet specifiek gematcht,
        // redirect naar het admin dashboard om doorvallen te voorkomen.
        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }
} elseif ($action === 'api_pending_jobs') {
    (new ApiController())->pendingJobs();
} elseif ($action === 'api_submit_result') {
    (new ApiController())->submitResult();
} elseif ($action === 'api_training_examples') {
    (new ApiController())->trainingExamples();
} elseif ($action === 'admin_regenerate_discovery') {
    header('Content-Type: application/json');
    // Verwijder oude discovery tree en queue nieuwe generatie
    $discPath = dirname(__DIR__) . '/storage/discovery_tree.json';
    if (file_exists($discPath)) unlink($discPath);
    $stmt = $db->prepare("INSERT INTO ai_jobs (topic, goal, job_type) VALUES ('Ontdekking', 'Genereer ontdekkers-boom', 'discovery')");
    $stmt->execute();
    $jobId = (int)$db->lastInsertId();
    echo json_encode(['redirect' => BASE_URL . "?action=discovery_waiting&job=$jobId"]);
    exit;
} elseif ($action === 'dynamic_status' && isset($_GET['job'])) {
    $controller->dynamicStatus((int)$_GET['job']);
} elseif ($action === 'dynamic_show') {
    $controller->showDynamic();
} elseif ($action === 'dynamic_select') {
    $controller->selectDynamic();
} elseif ($action === 'dynamic_back') {
    $controller->backDynamic();
} elseif ($action === 'start_discovery') {
    $controller->startDiscovery();
} elseif ($action === 'discovery_nav') {
    $controller->navigateDiscovery();
} elseif ($action === 'discovery_confirm') {
    $controller->showDiscoveryConfirm();
} elseif ($action === 'discovery_confirm_yes') {
    $controller->confirmDiscoveryYes();
} elseif ($action === 'discovery_waiting' && isset($_GET['job'])) {
    $controller->discoveryWaiting((int)$_GET['job']);
} elseif ($action === 'discovery_waiting_status' && isset($_GET['job'])) {
    $controller->discoveryWaitingStatus((int)$_GET['job']);
} elseif ($action === 'discovery_topic_waiting' && isset($_GET['job'])) {
    $controller->discoveryTopicWaiting((int)$_GET['job'], $_GET['topic_name'] ?? '');
} elseif ($action === 'discovery_topic_status' && isset($_GET['job'])) {
    $controller->discoveryTopicStatus((int)$_GET['job'], $_GET['topic_name'] ?? '');
} elseif ($action === 'topic_complete' && $topicId) {
    $controller->showComplete($topicId);
} elseif ($action === 'topic_followup' && $topicId) {
    $controller->requestFollowup($topicId);
} elseif ($action === 'topic_followup_waiting' && $topicId && isset($_GET['job'])) {
    $controller->showFollowupWaiting((int)$_GET['job'], $topicId);
} elseif ($action === 'topic_followup_status' && isset($_GET['job']) && $topicId) {
    $controller->applyFollowup((int)$_GET['job'], $topicId);
} elseif ($action === 'topic_followup_start' && $topicId) {
    $controller->startFollowup($topicId);
} elseif ($action === 'topic_followup_nav' && $topicId) {
    $controller->navigateFollowup($topicId);
} elseif ($topicId && $nodeId) {
    $controller->showNode($topicId, $nodeId);
} elseif ($topicId) {
    $controller->start($topicId);
} else {
    // Alles wat niet gematcht is, inclusief lege GET: start de dynamische AI-stroom
    $controller->index();
}
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
        $stmt = $this->db->prepare("SELECT root_node_id, name FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        $topic = $stmt->fetch();
        $rootId = $topic['root_node_id'];

        $_SESSION['history'] = [];
        $_SESSION['intent_state'] = [
            'topic'                => $topic['name'],
            'intent_probabilities' => [],
            'history'              => [],
            'current_node_id'      => $rootId,
            'completed'            => false,
            'topic_id'             => $id,
        ];
        header("Location: " . BASE_URL . "?topic=$id&node=$rootId");
        exit;
    }

    public function showNode(int $topicId, int $nodeId): void {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        // Update intent state when arriving via a chosen option
        $optionId = isset($_GET['opt']) ? (int)$_GET['opt'] : 0;
        if ($optionId > 0 && isset($_SESSION['intent_state'])) {
            $optStmt = $this->db->prepare("SELECT * FROM options WHERE id = ?");
            $optStmt->execute([$optionId]);
            $chosen = $optStmt->fetch();
            if ($chosen) {
                $state = &$_SESSION['intent_state'];
                $state['history'][] = [
                    'node'  => (int)$chosen['node_id'],
                    'label' => $chosen['label'],
                ];
                $state['current_node_id'] = $nodeId;
                $this->updateProbabilities($state, $chosen['label']);
            }
        }

        $stmt = $this->db->prepare("SELECT * FROM options WHERE node_id = ?");
        $stmt->execute([$nodeId]);
        $options = $stmt->fetchAll();

        if (!isset($_SESSION['history'])) $_SESSION['history'] = [];
        if (empty($_SESSION['history']) || end($_SESSION['history']) !== $nodeId) {
            $_SESSION['history'][] = $nodeId;
        }

        $this->render('node', [
            'topic'   => $topic,
            'options' => $options,
            'topicId' => $topicId,
        ]);
    }

    public function showComplete(int $topicId): void {
        // Record the terminal option choice in state
        $optionId = isset($_GET['opt']) ? (int)$_GET['opt'] : 0;
        if ($optionId > 0 && isset($_SESSION['intent_state'])) {
            $optStmt = $this->db->prepare("SELECT * FROM options WHERE id = ?");
            $optStmt->execute([$optionId]);
            $chosen = $optStmt->fetch();
            if ($chosen) {
                $state = &$_SESSION['intent_state'];
                $state['history'][] = ['node' => (int)$chosen['node_id'], 'label' => $chosen['label']];
                $state['current_node_id'] = null;
                $state['completed']       = true;
                $this->updateProbabilities($state, $chosen['label']);
            }
        }

        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        $state = $_SESSION['intent_state'] ?? null;
        $topIntent = null;
        if ($state && !empty($state['intent_probabilities'])) {
            arsort($state['intent_probabilities']);
            $topIntent = array_key_first($state['intent_probabilities']);
        }

        $this->render('topic_complete', [
            'topic'     => $topic,
            'topicId'   => $topicId,
            'state'     => $state,
            'topIntent' => $topIntent,
        ]);
    }

    public function requestFollowup(int $topicId): void {
        $state    = $_SESSION['intent_state'] ?? null;
        $topicName = $state['topic'] ?? 'Onbekend';

        $stmt = $this->db->prepare(
            "INSERT INTO ai_jobs (topic, goal, state_json) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $topicName,
            'Vervolg communicatie op basis van eerdere keuzes',
            json_encode($state),
        ]);
        $jobId = (int)$this->db->lastInsertId();

        header("Location: " . BASE_URL . "?action=topic_followup_waiting&job=$jobId&topic=$topicId");
        exit;
    }

    public function showFollowupWaiting(int $jobId, int $topicId): void {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        $this->render('topic_followup_waiting', [
            'topic'   => $topic,
            'topicId' => $topicId,
            'jobId'   => $jobId,
        ]);
    }

    public function applyFollowup(int $jobId, int $topicId): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("SELECT status, result_json, error_message FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        if (!$job) { echo json_encode(['status' => 'error', 'error' => 'Job niet gevonden']); exit; }
        if ($job['status'] === 'error') { echo json_encode(['status' => 'error', 'error' => $job['error_message']]); exit; }
        if ($job['status'] !== 'done')  { echo json_encode(['status' => 'pending']); exit; }

        $tree = json_decode($job['result_json'], true);
        $_SESSION['followup_tree']     = $tree;
        $_SESSION['followup_topic_id'] = $topicId;

        echo json_encode(['status' => 'done', 'redirect' => BASE_URL . "?action=topic_followup_start&topic=$topicId"]);
    }

    public function startFollowup(int $topicId): void {
        $tree = $_SESSION['followup_tree'] ?? null;
        if (!$tree) {
            header("Location: " . BASE_URL);
            exit;
        }

        $rootNode = $tree['nodes'][0] ?? null;
        if (!$rootNode) {
            header("Location: " . BASE_URL);
            exit;
        }

        // Reset intent state history but keep topic context
        if (isset($_SESSION['intent_state'])) {
            $_SESSION['intent_state']['history']              = [];
            $_SESSION['intent_state']['completed']            = false;
            $_SESSION['intent_state']['intent_probabilities'] = [];
            $_SESSION['intent_state']['current_node_id']      = $rootNode['id'];
        }
        $_SESSION['history'] = [];

        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        $nodes   = $tree['nodes'];
        $nodeMap = [];
        foreach ($nodes as $n) { $nodeMap[$n['id']] = $n; }

        $this->render('followup_node', [
            'topic'      => $topic,
            'topicId'    => $topicId,
            'nodeMap'    => $nodeMap,
            'currentId'  => $rootNode['id'],
        ]);
    }

    public function navigateFollowup(int $topicId): void {
        $tree = $_SESSION['followup_tree'] ?? null;
        if (!$tree) { header("Location: " . BASE_URL); exit; }

        $nodeId = isset($_GET['fnode']) ? (int)$_GET['fnode'] : 0;
        $optKey = $_GET['opt'] ?? '';

        $nodes   = $tree['nodes'];
        $nodeMap = [];
        foreach ($nodes as $n) { $nodeMap[$n['id']] = $n; }

        $node = $nodeMap[$nodeId] ?? null;
        if (!$node || !in_array($optKey, ['option_a', 'option_b'])) {
            header("Location: " . BASE_URL . "?action=topic_followup_start&topic=$topicId");
            exit;
        }

        $chosen  = $node[$optKey];
        $nextId  = $chosen['next_node_id'] ?? null;

        // Update state
        if (isset($_SESSION['intent_state'])) {
            $state = &$_SESSION['intent_state'];
            $state['history'][] = ['node' => $nodeId, 'label' => $chosen['label']];
            $state['current_node_id'] = $nextId;
            $state['completed']       = ($nextId === null);
            $this->updateProbabilities($state, $chosen['label']);
        }

        if ($nextId === null) {
            header("Location: " . BASE_URL . "?action=topic_complete&topic=$topicId");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        $this->render('followup_node', [
            'topic'     => $topic,
            'topicId'   => $topicId,
            'nodeMap'   => $nodeMap,
            'currentId' => $nextId,
        ]);
    }

    private function updateProbabilities(array &$state, string $label): void {
        $boost = 0.3;
        if (empty($state['intent_probabilities'])) {
            $state['intent_probabilities'][$label] = 1.0;
            return;
        }
        $count = count($state['intent_probabilities']);
        foreach ($state['intent_probabilities'] as $intent => &$prob) {
            if (mb_stripos($intent, $label) !== false || mb_stripos($label, $intent) !== false) {
                $prob += $boost;
            } else {
                $prob = max(0.0, $prob - $boost / $count);
            }
        }
        if (!array_key_exists($label, $state['intent_probabilities'])) {
            $state['intent_probabilities'][$label] = $boost;
        }
        $total = array_sum($state['intent_probabilities']);
        if ($total > 0) {
            foreach ($state['intent_probabilities'] as &$p) { $p /= $total; }
        }
    }

    public function back(int $topicId): void {
        if (isset($_SESSION['history']) && count($_SESSION['history']) > 1) {
            array_pop($_SESSION['history']); 
            $prev = array_pop($_SESSION['history']);
            header("Location: " . BASE_URL . "?topic=$topicId&node=$prev");
        } else {
            header("Location: " . BASE_URL);
        }
        exit;
    }

    public function reset(int $topicId): void {
        header("Location: " . BASE_URL . "?topic=$topicId");
        exit;
    }

    private function render(string $view, array $data): void {
        extract($data);
        include __DIR__ . "/../../views/layout.php";
    }
}
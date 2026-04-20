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
        $this->startDynamic();
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

    // ─── Dynamische opties (kern van het systeem) ─────────────────────────────

    /** Start een nieuwe sessie: lege geschiedenis, maak eerste dynamic_options job aan. */
    public function startDynamic(): void {
        $_SESSION['dynamic_history']      = [];
        $_SESSION['dynamic_options']      = [];
        $_SESSION['dynamic_options_stack'] = [];
        $_SESSION['dynamic_is_complete']  = false;
        $_SESSION['intent_state'] = [
            'topic' => 'Ontdekking', 'intent_probabilities' => [],
            'history' => [], 'current_node_id' => null,
            'completed' => false, 'topic_id' => 0,
        ];
        $jobId = $this->queueDynamicOptions([]);
        $this->render('dynamic_waiting', ['jobId' => $jobId, 'topicId' => 0, 'sentence' => '']);
    }

    /** Polling-endpoint: geeft status terug en slaat opties op in sessie als job klaar is. */
    public function dynamicStatus(int $jobId): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("SELECT status, result_json, error_message FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        if (!$job)                      { echo json_encode(['status' => 'error', 'error' => 'niet gevonden']); exit; }
        if ($job['status'] === 'error') { echo json_encode(['status' => 'error', 'error' => $job['error_message']]); exit; }
        if ($job['status'] !== 'done')  { echo json_encode(['status' => 'pending']); exit; }

        $result = json_decode($job['result_json'], true);
        // Sla opties op in sessie (stack voor back-knop)
        if (!empty($_SESSION['dynamic_options'])) {
            $_SESSION['dynamic_options_stack'][] = $_SESSION['dynamic_options'];
        }
        $_SESSION['dynamic_options']     = $result['options'];
        $_SESSION['dynamic_is_complete'] = (bool)($result['is_complete'] ?? false);
        echo json_encode(['status' => 'done', 'redirect' => BASE_URL . '?action=dynamic_show']);
    }

    /** Toont de huidige set opties. */
    public function showDynamic(): void {
        $options    = $_SESSION['dynamic_options'] ?? [];
        $isComplete = $_SESSION['dynamic_is_complete'] ?? false;
        $history    = $_SESSION['dynamic_history'] ?? [];
        $sentence   = $this->buildCandidateSentence($history);

        if (empty($options)) { $this->startDynamic(); return; }

        $this->render('dynamic_options', [
            'topicId'    => 0,
            'options'    => $options,
            'isComplete' => $isComplete,
            'sentence'   => $sentence,
            'canGoBack'  => !empty($_SESSION['dynamic_options_stack']),
        ]);
    }

    /** Verwerkt een keuze: update staat, maak volgende job aan. */
    public function selectDynamic(): void {
        $optIdx  = isset($_GET['oidx']) ? (int)$_GET['oidx'] : -1;
        $options = $_SESSION['dynamic_options'] ?? [];
        $opt     = ($optIdx >= 0 && isset($options[$optIdx])) ? $options[$optIdx] : null;
        if (!$opt) { header("Location: " . BASE_URL . "?action=dynamic_show"); exit; }

        // Voeg toe aan geschiedenis
        $history   = $_SESSION['dynamic_history'] ?? [];
        $history[] = $opt['label'];
        $_SESSION['dynamic_history'] = $history;

        // Update intent state
        if (isset($_SESSION['intent_state'])) {
            $state = &$_SESSION['intent_state'];
            $state['history'][] = ['node' => 0, 'label' => $opt['label']];
            $this->updateProbabilities($state, $opt['label']);
        }

        $isComplete = $_SESSION['dynamic_is_complete'] ?? false;
        if ($isComplete || !empty($opt['suggested_message'])) {
            // Eindoptie gekozen → bevestigingsscherm
            $_SESSION['discovery_confirm'] = [
                'target_topic'      => $opt['target_topic'] ?? $opt['label'],
                'suggested_message' => $opt['suggested_message'] ?? 'Ik wil ' . strtolower($opt['label']),
            ];
            header("Location: " . BASE_URL . "?action=discovery_confirm");
            exit;
        }

        // Nog niet compleet → genereer volgende opties
        $jobId = $this->queueDynamicOptions($history);
        $sentence = $this->buildCandidateSentence($history);
        $this->render('dynamic_waiting', ['jobId' => $jobId, 'topicId' => 0, 'sentence' => $sentence]);
    }

    /** Terug naar vorige set opties (geen AI-call nodig). */
    public function backDynamic(): void {
        $stack = $_SESSION['dynamic_options_stack'] ?? [];
        if (empty($stack)) { $this->startDynamic(); return; }

        $prev = array_pop($stack);
        $_SESSION['dynamic_options_stack'] = $stack;
        $_SESSION['dynamic_options']       = $prev;
        $_SESSION['dynamic_is_complete']   = false;

        // Verwijder laatste item uit geschiedenis
        $history = $_SESSION['dynamic_history'] ?? [];
        array_pop($history);
        $_SESSION['dynamic_history'] = $history;

        // Herstel intent state
        if (isset($_SESSION['intent_state'])) {
            $histItems = $_SESSION['intent_state']['history'] ?? [];
            array_pop($histItems);
            $_SESSION['intent_state']['history'] = $histItems;
        }

        header("Location: " . BASE_URL . "?action=dynamic_show");
        exit;
    }

    private function queueDynamicOptions(array $history): int {
        $stateJson = json_encode(['history' => $history]);
        $stmt = $this->db->prepare(
            "INSERT INTO ai_jobs (topic, goal, job_type, state_json) VALUES ('Ontdekking', '', 'dynamic_options', ?)"
        );
        $stmt->execute([$stateJson]);
        return (int)$this->db->lastInsertId();
    }

    // ─── Discovery (statische boom — behouden als fallback) ───────────────────

    public function startDiscovery(): void {
        $tree = $this->loadDiscoveryTree();
        if (!$tree) {
            $this->queueDiscovery();
            return;
        }
        $nodeMap = [];
        foreach ($tree['nodes'] as $n) { $nodeMap[$n['id']] = $n; }
        $_SESSION['discovery_nodeMap'] = $nodeMap;
        $_SESSION['intent_state'] = [
            'topic' => 'Ontdekking', 'intent_probabilities' => [],
            'history' => [], 'current_node_id' => $tree['nodes'][0]['id'],
            'completed' => false, 'topic_id' => 0,
        ];
        $this->render('discovery_node', [
            'topicId'   => 0,
            'nodeMap'   => $nodeMap,
            'currentId' => $tree['nodes'][0]['id'],
            'sentence'  => '',
        ]);
    }

    public function navigateDiscovery(): void {
        $nodeMap = $_SESSION['discovery_nodeMap'] ?? null;
        if (!$nodeMap) { header("Location: " . BASE_URL); exit; }

        $nodeId = (int)($_GET['dnode'] ?? 0);
        $optIdx = isset($_GET['oidx']) ? (int)$_GET['oidx'] : -1;
        $node   = $nodeMap[$nodeId] ?? null;
        $opts   = $node['options'] ?? [];

        if (!$node || $optIdx < 0 || !isset($opts[$optIdx])) {
            header("Location: " . BASE_URL); exit;
        }

        $chosen      = $opts[$optIdx];
        $nextId      = $chosen['next_node_id'] ?? null;
        $targetTopic = $chosen['target_topic'] ?? null;

        if (isset($_SESSION['intent_state'])) {
            $state = &$_SESSION['intent_state'];
            $state['history'][] = ['node' => $nodeId, 'label' => $chosen['label']];
            $state['current_node_id'] = $nextId;
            $this->updateProbabilities($state, $chosen['label']);
        }

        if ($targetTopic !== null) {
            $suggestedMessage = $chosen['suggested_message'] ?? ('Ik wil ' . strtolower($chosen['label']));
            $_SESSION['discovery_confirm'] = [
                'target_topic'      => $targetTopic,
                'suggested_message' => $suggestedMessage,
            ];
            header("Location: " . BASE_URL . "?action=discovery_confirm");
            exit;
        }

        $history  = $_SESSION['intent_state']['history'] ?? [];
        $sentence = $this->buildCandidateSentence($history);

        $this->render('discovery_node', [
            'topicId'   => 0,
            'nodeMap'   => $nodeMap,
            'currentId' => $nextId,
            'sentence'  => $sentence,
        ]);
    }

    private function buildCandidateSentence(array $history): string {
        if (empty($history)) return '';
        $labels = array_column($history, 'label');
        if (count($labels) === 1) return 'Ik wil iets zeggen over: ' . $labels[0];
        $last = array_pop($labels);
        return 'Ik wil zeggen over ' . implode(', ', $labels) . ': ' . $last;
    }

    public function showDiscoveryConfirm(): void {
        $confirm = $_SESSION['discovery_confirm'] ?? null;
        if (!$confirm) { header("Location: " . BASE_URL); exit; }
        $this->render('discovery_confirm', [
            'topicId'          => 0,
            'suggestedMessage' => $confirm['suggested_message'],
            'targetTopic'      => $confirm['target_topic'],
        ]);
    }

    public function confirmDiscoveryYes(): void {
        $confirm = $_SESSION['discovery_confirm'] ?? null;
        if (!$confirm) { header("Location: " . BASE_URL); exit; }

        $targetTopic      = $confirm['target_topic'];
        $suggestedMessage = $confirm['suggested_message'];

        // Update intent state met de uiteindelijke intentie
        if (isset($_SESSION['intent_state'])) {
            $_SESSION['intent_state']['topic']     = $targetTopic;
            $_SESSION['intent_state']['completed'] = false;
        }

        // Zoek bestaand topic
        $stmt = $this->db->prepare(
            "SELECT id, root_node_id FROM topics WHERE LOWER(name) LIKE LOWER(?)"
        );
        $stmt->execute(['%' . $targetTopic . '%']);
        $dbTopic = $stmt->fetch();

        if ($dbTopic) {
            $_SESSION['history'] = [];
            header("Location: " . BASE_URL . "?topic={$dbTopic['id']}&node={$dbTopic['root_node_id']}");
        } else {
            $goal = "Communicatieboom voor: \"$suggestedMessage\"";
            $stmt = $this->db->prepare(
                "INSERT INTO ai_jobs (topic, goal, job_type) VALUES (?, ?, 'topic_auto')"
            );
            $stmt->execute([$targetTopic, $goal]);
            $jobId = (int)$this->db->lastInsertId();
            header("Location: " . BASE_URL . "?action=discovery_topic_waiting&job=$jobId&topic_name=" . urlencode($targetTopic));
        }
        exit;
    }

    public function discoveryWaiting(int $jobId): void {
        $this->render('discovery_waiting', ['jobId' => $jobId, 'topicId' => 0]);
    }

    public function discoveryWaitingStatus(int $jobId): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("SELECT status, result_json FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        if (!$job)                       { echo json_encode(['status' => 'error', 'error' => 'Job niet gevonden']); exit; }
        if ($job['status'] === 'error')  { echo json_encode(['status' => 'error']); exit; }
        if ($job['status'] !== 'done')   { echo json_encode(['status' => 'pending']); exit; }

        $tree = json_decode($job['result_json'], true);
        $this->saveDiscoveryTree($tree);
        $this->db->prepare("DELETE FROM ai_jobs WHERE id = ?")->execute([$jobId]);
        echo json_encode(['status' => 'done', 'redirect' => BASE_URL . "?action=start_discovery"]);
    }

    public function discoveryTopicWaiting(int $jobId, string $topicName): void {
        $this->render('discovery_topic_waiting', ['jobId' => $jobId, 'topicName' => $topicName, 'topicId' => 0]);
    }

    public function discoveryTopicStatus(int $jobId, string $topicName): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("SELECT status, result_json FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        if (!$job)                       { echo json_encode(['status' => 'error', 'error' => 'Niet gevonden']); exit; }
        if ($job['status'] === 'error')  { echo json_encode(['status' => 'error', 'error' => $job['error_message'] ?? '']); exit; }
        if ($job['status'] !== 'done')   { echo json_encode(['status' => 'pending']); exit; }

        $tree    = json_decode($job['result_json'], true);
        $topicId = $this->autoSaveTopicFromTree($topicName, $tree, $jobId);
        $_SESSION['history'] = [];
        echo json_encode(['status' => 'done', 'redirect' => BASE_URL . "?topic=$topicId"]);
    }

    private function autoSaveTopicFromTree(string $name, array $tree, int $jobId): int {
        $nodes = $tree['nodes'] ?? [];
        $this->db->beginTransaction();
        try {
            $stmtTopic = $this->db->prepare("INSERT INTO topics (name, root_node_id) VALUES (?, NULL)");
            $stmtTopic->execute([htmlspecialchars($name, ENT_QUOTES, 'UTF-8')]);
            $topicId = (int)$this->db->lastInsertId();

            $idMap = [];
            foreach ($nodes as $n) {
                $stmtNode = $this->db->prepare("INSERT INTO nodes (topic_id) VALUES (?)");
                $stmtNode->execute([$topicId]);
                $idMap[$n['id']] = (int)$this->db->lastInsertId();
            }

            foreach ($nodes as $n) {
                $realNodeId = $idMap[$n['id']];
                foreach (['option_a', 'option_b'] as $optKey) {
                    $opt        = $n[$optKey];
                    $nextRealId = ($opt['next_node_id'] !== null && isset($idMap[$opt['next_node_id']]))
                        ? $idMap[$opt['next_node_id']] : null;
                    $this->db->prepare(
                        "INSERT INTO options (node_id, label, image_hint, image_url, next_node_id) VALUES (?,?,?,?,?)"
                    )->execute([
                        $realNodeId,
                        htmlspecialchars($opt['label'] ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($opt['image_hint'] ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($opt['image_url'] ?? '', ENT_QUOTES, 'UTF-8'),
                        $nextRealId,
                    ]);
                }
            }

            if (isset($idMap[1])) {
                $this->db->prepare("UPDATE topics SET root_node_id = ? WHERE id = ?")->execute([$idMap[1], $topicId]);
            }
            $this->db->prepare("DELETE FROM ai_jobs WHERE id = ?")->execute([$jobId]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $topicId;
    }

    private function queueDiscovery(): void {
        // Hergebruik bestaande pending job als die er al is
        $existing = $this->db->query(
            "SELECT id FROM ai_jobs WHERE job_type='discovery' AND status='pending' LIMIT 1"
        )->fetch();

        if ($existing) {
            $jobId = (int)$existing['id'];
        } else {
            $this->db->prepare(
                "INSERT INTO ai_jobs (topic, goal, job_type) VALUES ('Ontdekking', 'Genereer ontdekkers-boom', 'discovery')"
            )->execute();
            $jobId = (int)$this->db->lastInsertId();
        }

        $this->render('discovery_waiting', ['jobId' => $jobId, 'topicId' => 0]);
    }

    private function loadDiscoveryTree(): ?array {
        $path = __DIR__ . '/../../storage/discovery_tree.json';
        if (!file_exists($path)) return null;
        $data = json_decode(file_get_contents($path), true);
        return ($data && isset($data['nodes'])) ? $data : null;
    }

    private function saveDiscoveryTree(array $tree): void {
        file_put_contents(__DIR__ . '/../../storage/discovery_tree.json', json_encode($tree));
    }

    // ─── Back / Reset ─────────────────────────────────────────────────────────

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
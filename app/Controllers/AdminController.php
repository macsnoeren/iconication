<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class AdminController {
    private \PDO $db;

    public function __construct() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: " . BASE_URL . "?action=login");
            exit;
        }
        $this->db = Database::getConnection();
    }

    public function index(): void {
        $topics = $this->db->query("SELECT * FROM topics")->fetchAll();
        $view = 'admin_dashboard';
        include __DIR__ . "/../../views/layout.php";
    }

    public function deleteTopic(int $id): void {
        // Verwijder eerst alle nodes en opties die bij dit topic horen
        $stmtNodes = $this->db->prepare("SELECT id FROM nodes WHERE topic_id = ?");
        $stmtNodes->execute([$id]);
        $nodeIds = $stmtNodes->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($nodeIds)) {
            $placeholders = implode(',', array_fill(0, count($nodeIds), '?'));
            $this->db->prepare("DELETE FROM options WHERE node_id IN ($placeholders)")->execute($nodeIds);
            $this->db->prepare("DELETE FROM nodes WHERE id IN ($placeholders)")->execute($nodeIds);
        }

        $stmt = $this->db->prepare("DELETE FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    public function addTopic(): void {
        $view = 'admin_topic_form';
        $topic = ['id' => null, 'name' => '', 'root_node_id' => null];
        include __DIR__ . "/../../views/layout.php";
    }

    public function saveTopic(?int $topicId = null): void {
        $name = $_POST['name'] ?? '';
        $rootNodeId = $_POST['root_node_id'] ?? null;
        $rootNodeId = ($rootNodeId !== '' && $rootNodeId !== null) ? (int)$rootNodeId : null;

        if (empty($name)) {
            // Error handling
            header("Location: " . BASE_URL . "?action=admin"); // Redirect back for now
            exit;
        }

        if ($topicId) {
            $stmt = $this->db->prepare("UPDATE topics SET name = ?, root_node_id = ? WHERE id = ?");
            $stmt->execute([$name, $rootNodeId, $topicId]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO topics (name, root_node_id) VALUES (?, ?)");
            $stmt->execute([$name, $rootNodeId]);
        }

        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    public function showTopicNodes(int $topicId): void {
        $stmtTopic = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmtTopic->execute([$topicId]);
        $topic = $stmtTopic->fetch();

        $stmtNodes = $this->db->prepare("SELECT * FROM nodes WHERE topic_id = ?");
        $stmtNodes->execute([$topicId]);
        $nodes = $stmtNodes->fetchAll();

        $view = 'admin_topic_nodes';
        include __DIR__ . "/../../views/layout.php";
    }

    public function addNode(int $topicId): void {
        $node = ['id' => null, 'topic_id' => $topicId];
        $options = [
            ['label' => '', 'image_url' => '', 'next_node_id' => null],
            ['label' => '', 'image_url' => '', 'next_node_id' => null]
        ];
        $view = 'admin_node_form';
        include __DIR__ . "/../../views/layout.php";
    }

    public function editNode(int $topicId, int $nodeId): void {
        $stmtNode = $this->db->prepare("SELECT * FROM nodes WHERE id = ? AND topic_id = ?");
        $stmtNode->execute([$nodeId, $topicId]);
        $node = $stmtNode->fetch();

        if (!$node) {
            header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
            exit;
        }

        $stmtOptions = $this->db->prepare("SELECT * FROM options WHERE node_id = ? ORDER BY id ASC");
        $stmtOptions->execute([$nodeId]);
        $options = $stmtOptions->fetchAll();

        // Zorg dat er altijd 2 opties zijn voor het formulier
        while (count($options) < 2) {
            $options[] = ['id' => null, 'label' => '', 'image_url' => '', 'next_node_id' => null];
        }

        $view = 'admin_node_form';
        include __DIR__ . "/../../views/layout.php";
    }

    public function saveNode(int $topicId, ?int $nodeId = null): void {
        $optionLabels = $_POST['option_label'] ?? [];
        $optionImages = $_POST['option_image'] ?? [];
        $optionNextNodes = $_POST['option_next_node'] ?? [];
        $optionIds = $_POST['option_id'] ?? [];

        if ($nodeId) {
            // Update bestaande node (alleen topic_id is relevant hier)
            $stmt = $this->db->prepare("UPDATE nodes SET topic_id = ? WHERE id = ?");
            $stmt->execute([$topicId, $nodeId]);
        } else {
            // Nieuwe node aanmaken
            $stmt = $this->db->prepare("INSERT INTO nodes (topic_id) VALUES (?)");
            $stmt->execute([$topicId]);
            $nodeId = (int)$this->db->lastInsertId();
        }

        // Opties opslaan
        for ($i = 0; $i < 2; $i++) { // We verwachten altijd 2 opties
            $label = $optionLabels[$i] ?? '';
            $image = $optionImages[$i] ?? '';
            $nextNode = ($optionNextNodes[$i] !== '' && $optionNextNodes[$i] !== null) ? (int)$optionNextNodes[$i] : null;
            $optionId = !empty($optionIds[$i]) ? (int)$optionIds[$i] : null;

            if ($optionId !== null) {
                $stmt = $this->db->prepare("UPDATE options SET label = ?, image_url = ?, next_node_id = ? WHERE id = ?");
                $stmt->execute([$label, $image, $nextNode, $optionId]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nodeId, $label, $image, $nextNode]);
            }
        }

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
        exit;
    }

    public function deleteNode(int $topicId, int $nodeId): void {
        // Verwijder eerst de opties van de node
        $stmtOptions = $this->db->prepare("DELETE FROM options WHERE node_id = ?");
        $stmtOptions->execute([$nodeId]);

        // Verwijder dan de node zelf
        $stmtNode = $this->db->prepare("DELETE FROM nodes WHERE id = ?");
        $stmtNode->execute([$nodeId]);

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=$topicId");
        exit;
    }

    public function getExistingImages(): void {
        $stmt = $this->db->query("SELECT DISTINCT image_url FROM options WHERE image_url IS NOT NULL AND image_url != '' ORDER BY label ASC");
        $images = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        header('Content-Type: application/json');
        echo json_encode($images);
        exit;
    }

    // ─── AI generatie (job-gebaseerd) ────────────────────────────────────────

    /** Toon het formulier om een nieuw onderwerp via AI te genereren. */
    public function aiGenerateTopic(): void {
        $view = 'admin_ai_generate';
        include __DIR__ . "/../../views/layout.php";
    }

    /** Sla het verzoek op als job in de DB en ga naar het wachtscherm. */
    public function aiQueueTopic(): void {
        $topic = trim($_POST['topic'] ?? '');
        $goal  = trim($_POST['goal']  ?? '');

        if ($topic === '') {
            $_SESSION['ai_error'] = 'Voer een onderwerp in.';
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }
        if (mb_strlen($topic) > 200) {
            $_SESSION['ai_error'] = 'Onderwerp is te lang (max 200 tekens).';
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }
        if (mb_strlen($goal) > 500) {
            $_SESSION['ai_error'] = 'Doel is te lang (max 500 tekens).';
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO ai_jobs (topic, goal) VALUES (?, ?)"
        );
        $stmt->execute([$topic, $goal]);
        $jobId = (int)$this->db->lastInsertId();

        header("Location: " . BASE_URL . "?action=admin_ai_waiting&job=" . $jobId);
        exit;
    }

    /** Toon het wachtscherm voor een lopende job. */
    public function aiWaiting(int $jobId): void {
        $stmt = $this->db->prepare("SELECT * FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        if (!$job) {
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        // Als de job al klaar is, direct doorsturen
        if ($job['status'] === 'done') {
            header("Location: " . BASE_URL . "?action=admin_ai_preview&job=" . $jobId);
            exit;
        }
        if ($job['status'] === 'error') {
            $_SESSION['ai_error'] = 'Worker fout: ' . htmlspecialchars($job['error_message'] ?? 'onbekend');
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        $view = 'admin_ai_waiting';
        include __DIR__ . "/../../views/layout.php";
    }

    /** JSON status-endpoint voor JS polling op het wachtscherm. */
    public function aiJobStatus(int $jobId): void {
        $stmt = $this->db->prepare("SELECT status, error_message FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        header('Content-Type: application/json');
        if (!$job) {
            http_response_code(404);
            echo json_encode(['status' => 'not_found']);
            exit;
        }
        echo json_encode(['status' => $job['status'], 'error' => $job['error_message']]);
        exit;
    }

    /** Toon de preview van een voltooide job. */
    public function aiPreviewJob(int $jobId): void {
        $stmt = $this->db->prepare("SELECT * FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        if (!$job || $job['status'] !== 'done') {
            header("Location: " . BASE_URL . "?action=admin_ai_waiting&job=" . $jobId);
            exit;
        }

        $tree    = json_decode($job['result_json'], true);
        $ai_goal = $job['goal'];
        // $job beschikbaar stellen aan de view voor het job_id hidden field
        $view    = 'admin_ai_preview';
        include __DIR__ . "/../../views/layout.php";
    }

    /** Sla de (eventueel aangepaste) AI-boom op in de database. */
    public function aiSaveTopic(): void {
        $topicName = trim($_POST['topic_name'] ?? '');
        $jobId     = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
        $rawNodes  = $_POST['nodes'] ?? [];

        if ($topicName === '') {
            $_SESSION['ai_error'] = 'Onderwerpnaam mag niet leeg zijn.';
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }
        if (empty($rawNodes)) {
            $_SESSION['ai_error'] = 'Geen nodes ontvangen.';
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        // Sanitize labels
        $nodes = [];
        foreach ($rawNodes as $n) {
            $nodes[] = [
                'id'       => (int)($n['id'] ?? 0),
                'option_a' => [
                    'label'        => mb_substr(trim($n['option_a']['label'] ?? ''), 0, 100),
                    'image_hint'   => mb_substr(trim($n['option_a']['image_hint'] ?? ''), 0, 100),
                    'image_url'    => mb_substr(trim($n['option_a']['image_url'] ?? ''), 0, 500),
                    'next_node_id' => ($n['option_a']['next_node_id'] ?? '') !== ''
                        ? (int)$n['option_a']['next_node_id'] : null,
                ],
                'option_b' => [
                    'label'        => mb_substr(trim($n['option_b']['label'] ?? ''), 0, 100),
                    'image_hint'   => mb_substr(trim($n['option_b']['image_hint'] ?? ''), 0, 100),
                    'image_url'    => mb_substr(trim($n['option_b']['image_url'] ?? ''), 0, 500),
                    'next_node_id' => ($n['option_b']['next_node_id'] ?? '') !== ''
                        ? (int)$n['option_b']['next_node_id'] : null,
                ],
            ];
        }

        $this->db->beginTransaction();
        try {
            // 1. Maak het topic aan
            $stmtTopic = $this->db->prepare("INSERT INTO topics (name, root_node_id) VALUES (?, NULL)");
            $stmtTopic->execute([htmlspecialchars($topicName, ENT_QUOTES, 'UTF-8')]);
            $topicId = (int)$this->db->lastInsertId();

            // 2. Maak alle nodes aan; bouw mapping temp_id → real_id
            $idMap = [];
            foreach ($nodes as $n) {
                $stmtNode = $this->db->prepare("INSERT INTO nodes (topic_id) VALUES (?)");
                $stmtNode->execute([$topicId]);
                $idMap[$n['id']] = (int)$this->db->lastInsertId();
            }

            // 3. Voeg opties in met vertaalde next_node_ids
            foreach ($nodes as $n) {
                $realNodeId = $idMap[$n['id']];
                foreach (['option_a', 'option_b'] as $optKey) {
                    $opt        = $n[$optKey];
                    $nextRealId = ($opt['next_node_id'] !== null && isset($idMap[$opt['next_node_id']]))
                        ? $idMap[$opt['next_node_id']] : null;
                    $stmtOpt = $this->db->prepare(
                        "INSERT INTO options (node_id, label, image_hint, image_url, next_node_id) VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmtOpt->execute([
                        $realNodeId,
                        htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($opt['image_hint'], ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($opt['image_url'], ENT_QUOTES, 'UTF-8'),
                        $nextRealId,
                    ]);
                }
            }

            // 4. Root node instellen
            if (isset($idMap[1])) {
                $stmtRoot = $this->db->prepare("UPDATE topics SET root_node_id = ? WHERE id = ?");
                $stmtRoot->execute([$idMap[1], $topicId]);
            }

            // 5. Job als verwerkt markeren
            if ($jobId) {
                $this->db->prepare("DELETE FROM ai_jobs WHERE id = ?")->execute([$jobId]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $_SESSION['ai_error'] = 'Databasefout bij opslaan: ' . htmlspecialchars($e->getMessage());
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=" . $topicId);
        exit;
    }
}
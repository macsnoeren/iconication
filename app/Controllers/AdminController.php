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
        $trees  = $this->db->query("SELECT * FROM option_trees ORDER BY created_at DESC")->fetchAll();
        $view   = 'admin_dashboard';
        include __DIR__ . "/../../views/layout.php";
    }

    // Maak een dynamische AI-boom aan (of activeer bestaande) en stel hem in als actief.
    public function enableDynamicMode(): void {
        // Zet alle bestaande bomen op 'static'
        $this->db->exec("UPDATE option_trees SET generation_mode = 'static'");

        // Controleer of er al een dynamic boom bestaat
        $existing = $this->db->query(
            "SELECT id FROM option_trees WHERE name = 'AI Gesprek' LIMIT 1"
        )->fetch();

        if ($existing) {
            $this->db->prepare(
                "UPDATE option_trees SET generation_mode = 'dynamic', status = 'ready' WHERE id = ?"
            )->execute([$existing['id']]);
        } else {
            // Zorg dat er een profiel is
            $profileCount = $this->db->query("SELECT COUNT(*) FROM user_profiles")->fetchColumn();
            if ($profileCount == 0) {
                $this->db->exec("INSERT INTO user_profiles (id, name, language, support_level) VALUES (1, 'Standaard gebruiker', 'nl', 2)");
            }
            $this->db->prepare(
                "INSERT INTO option_trees (profile_id, name, language, status, generation_mode) VALUES (1, 'AI Gesprek', 'nl', 'ready', 'dynamic')"
            )->execute();
        }

        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    // Zet alles terug op static (gebruik vooraf gegenereerde bomen).
    public function enableStaticMode(): void {
        $this->db->exec("UPDATE option_trees SET generation_mode = 'static'");
        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    // Kopieert de huidige staat van een dynamische boom als nieuwe statische boom.
    public function saveAsStaticTree(int $treeId): void {
        $stmt = $this->db->prepare("SELECT * FROM option_trees WHERE id = ?");
        $stmt->execute([$treeId]);
        $tree = $stmt->fetch();

        if (!$tree) { header("Location: " . BASE_URL . "?action=admin"); exit; }

        $cntStmt = $this->db->prepare("SELECT COUNT(*) FROM tree_nodes WHERE tree_id = ?");
        $cntStmt->execute([$treeId]);
        if ((int)$cntStmt->fetchColumn() === 0) {
            header("Location: " . BASE_URL . "?action=admin"); exit;
        }

        $name = ($tree['name'] ?? 'AI boom') . ' — ' . date('d-m-Y H:i');
        (new \App\Domain\Content\TreeRepository())->copyAsStaticTree($treeId, $name);

        header("Location: " . BASE_URL . "?action=admin");
        exit;
    }

    // Verander de generation_mode van één specifieke boom.
    public function setTreeMode(int $treeId, string $mode): void {
        $mode = $mode === 'dynamic' ? 'dynamic' : 'static';
        if ($mode === 'dynamic') {
            // Zet andere bomen op static zodat er altijd maar één dynamic actief is
            $this->db->exec("UPDATE option_trees SET generation_mode = 'static'");
        }
        $this->db->prepare(
            "UPDATE option_trees SET generation_mode = ? WHERE id = ?"
        )->execute([$mode, $treeId]);
        header("Location: " . BASE_URL . "?action=admin");
        exit;
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

    // ─── AI Training ──────────────────────────────────────────────────────────

    /** Toon de trainingspagina: alle topics met een checkbox om ze als voorbeeld te markeren. */
    public function showTraining(): void {
        $topics = $this->db->query(
            "SELECT t.*, e.id AS example_id, e.notes
             FROM topics t
             LEFT JOIN ai_training_examples e ON e.topic_id = t.id
             ORDER BY t.name ASC"
        )->fetchAll();
        $view = 'admin_training';
        include __DIR__ . "/../../views/layout.php";
    }

    /** Sla de training-selectie op (welke topics als voorbeeld dienen). */
    public function saveTraining(): void {
        $selected = $_POST['example_topics'] ?? [];
        $notes    = $_POST['notes'] ?? [];

        // Verwijder alle huidige voorbeelden
        $this->db->exec("DELETE FROM ai_training_examples");

        // Voeg de geselecteerde topics opnieuw in
        $stmt = $this->db->prepare(
            "INSERT INTO ai_training_examples (topic_id, notes) VALUES (?, ?)"
        );
        foreach ($selected as $topicId) {
            $topicId = (int)$topicId;
            $note    = mb_substr(trim($notes[$topicId] ?? ''), 0, 500);
            $stmt->execute([$topicId, $note]);
        }

        header("Location: " . BASE_URL . "?action=admin_training#saved");
        exit;
    }

    // ─── Topic overzicht bewerken ─────────────────────────────────────────────

    /** Toon het bewerkingsoverzicht voor een heel topic (alle nodes tegelijk). */
    public function showTopicEdit(int $topicId): void {
        $stmtTopic = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmtTopic->execute([$topicId]);
        $topic = $stmtTopic->fetch();

        if (!$topic) {
            header("Location: " . BASE_URL . "?action=admin");
            exit;
        }

        $stmtNodes = $this->db->prepare("SELECT * FROM nodes WHERE topic_id = ? ORDER BY id ASC");
        $stmtNodes->execute([$topicId]);
        $rawNodes = $stmtNodes->fetchAll();

        // Laad opties per node
        $nodes = [];
        foreach ($rawNodes as $node) {
            $stmtOpts = $this->db->prepare("SELECT * FROM options WHERE node_id = ? ORDER BY id ASC");
            $stmtOpts->execute([$node['id']]);
            $opts = $stmtOpts->fetchAll();
            // Zorg altijd voor 2 opties
            while (count($opts) < 2) {
                $opts[] = ['id' => null, 'label' => '', 'image_url' => '', 'image_hint' => '', 'next_node_id' => null];
            }
            $nodes[] = ['node' => $node, 'options' => $opts];
        }

        $view = 'admin_topic_edit';
        include __DIR__ . "/../../views/layout.php";
    }

    /** Sla alle wijzigingen in het bewerkingsoverzicht op. */
    public function saveTopicEdit(int $topicId): void {
        $topicName = trim($_POST['topic_name'] ?? '');
        $rootNodeId = !empty($_POST['root_node_id']) ? (int)$_POST['root_node_id'] : null;
        $rawNodes   = $_POST['nodes'] ?? [];

        if ($topicName === '') {
            header("Location: " . BASE_URL . "?action=admin_topic_edit&topic=$topicId");
            exit;
        }

        // Topic naam en root bijwerken
        $this->db->prepare("UPDATE topics SET name = ?, root_node_id = ? WHERE id = ?")
            ->execute([$topicName, $rootNodeId, $topicId]);

        // Opties bijwerken
        foreach ($rawNodes as $n) {
            foreach (['option_a', 'option_b'] as $optKey) {
                $opt      = $n[$optKey] ?? [];
                $optionId = !empty($opt['id']) ? (int)$opt['id'] : null;
                if (!$optionId) continue;

                $label    = mb_substr(trim($opt['label']     ?? ''), 0, 100);
                $hint     = mb_substr(trim($opt['image_hint'] ?? ''), 0, 100);
                $imageUrl = mb_substr(trim($opt['image_url']  ?? ''), 0, 500);
                $nextId   = ($opt['next_node_id'] ?? '') !== '' ? (int)$opt['next_node_id'] : null;

                $this->db->prepare(
                    "UPDATE options SET label = ?, image_hint = ?, image_url = ?, next_node_id = ? WHERE id = ?"
                )->execute([
                    htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($hint, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'),
                    $nextId,
                    $optionId,
                ]);
            }
        }

        $this->syncTopicToTree($topicId);

        header("Location: " . BASE_URL . "?action=admin_topic_edit&topic=$topicId#saved");
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

            // 6. Sync naar nieuwe option_trees/tree_nodes schema
            $this->syncTopicToTree($topicId);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $_SESSION['ai_error'] = 'Databasefout bij opslaan: ' . htmlspecialchars($e->getMessage());
            header("Location: " . BASE_URL . "?action=admin_ai_generate");
            exit;
        }

        header("Location: " . BASE_URL . "?action=admin_topic_nodes&topic=" . $topicId);
        exit;
    }

    // ── Tree node beheer ─────────────────────────────────────────────────────

    public function showTreeNodes(int $treeId): void
    {
        $stmt = $this->db->prepare("SELECT * FROM option_trees WHERE id = ?");
        $stmt->execute([$treeId]);
        $tree = $stmt->fetch();
        if (!$tree) { header("Location: " . BASE_URL . "?action=admin"); exit; }

        // Haal nodes op met parent-label voor context
        $stmt = $this->db->prepare(
            "SELECT n.*, p.label AS parent_label
             FROM tree_nodes n
             LEFT JOIN tree_nodes p ON p.id = n.parent_id
             WHERE n.tree_id = ?
             ORDER BY n.depth ASC, n.sort_order ASC"
        );
        $stmt->execute([$treeId]);
        $nodes = $stmt->fetchAll();

        $view = 'admin_tree_nodes';
        include __DIR__ . "/../../views/layout.php";
    }

    public function editTreeNode(int $nodeId): void
    {
        $stmt = $this->db->prepare("SELECT * FROM tree_nodes WHERE id = ?");
        $stmt->execute([$nodeId]);
        $node = $stmt->fetch();
        if (!$node) { header("Location: " . BASE_URL . "?action=admin"); exit; }

        $view = 'admin_edit_tree_node';
        include __DIR__ . "/../../views/layout.php";
    }

    public function saveTreeNode(int $nodeId): void
    {
        $label            = mb_substr(trim($_POST['label'] ?? ''), 0, 100);
        $imageUrl         = mb_substr(trim($_POST['image_url'] ?? ''), 0, 500);
        $suggestedMessage = mb_substr(trim($_POST['suggested_message'] ?? ''), 0, 500);
        $isLeaf           = isset($_POST['is_leaf']) ? 1 : 0;

        $stmt = $this->db->prepare(
            "SELECT tree_id FROM tree_nodes WHERE id = ?"
        );
        $stmt->execute([$nodeId]);
        $row = $stmt->fetch();
        $treeId = $row ? (int)$row['tree_id'] : 0;

        $this->db->prepare(
            "UPDATE tree_nodes SET label = ?, image_url = ?, suggested_message = ?, is_leaf = ? WHERE id = ?"
        )->execute([$label, $imageUrl, $suggestedMessage ?: null, $isLeaf, $nodeId]);

        header("Location: " . BASE_URL . "?action=admin_tree_nodes&tree=$treeId");
        exit;
    }

    public function deleteTreeNode(int $nodeId): void
    {
        $stmt = $this->db->prepare("SELECT tree_id FROM tree_nodes WHERE id = ?");
        $stmt->execute([$nodeId]);
        $row = $stmt->fetch();
        $treeId = $row ? (int)$row['tree_id'] : 0;

        // Verwijder ook alle kinderen recursief
        $this->deleteNodeRecursive($nodeId);

        header("Location: " . BASE_URL . "?action=admin_tree_nodes&tree=$treeId");
        exit;
    }

    private function deleteNodeRecursive(int $nodeId): void
    {
        $stmt = $this->db->prepare("SELECT id FROM tree_nodes WHERE parent_id = ?");
        $stmt->execute([$nodeId]);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $childId) {
            $this->deleteNodeRecursive((int)$childId);
        }
        $this->db->prepare("DELETE FROM tree_nodes WHERE id = ?")->execute([$nodeId]);
    }

    // ── Sync helpers ──────────────────────────────────────────────────────────

    // Synchroniseert een topic uit de oude tabellen naar option_trees/tree_nodes.
    // Verwijdert eerst een eventueel bestaande boom voor dit topic.
    private function syncTopicToTree(int $topicId): void
    {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();
        if (!$topic) return;

        // Verwijder bestaande boom voor dit topic
        $this->db->prepare("DELETE FROM tree_nodes WHERE tree_id = ?")->execute([$topicId]);
        $this->db->prepare("DELETE FROM option_trees WHERE id = ?")->execute([$topicId]);

        // Maak standaard profiel als het er nog niet is
        $profileCount = $this->db->query("SELECT COUNT(*) FROM user_profiles")->fetchColumn();
        if ($profileCount == 0) {
            $this->db->exec("INSERT INTO user_profiles (id, name, language, support_level) VALUES (1, 'Standaard gebruiker', 'nl', 2)");
        }

        $this->db->prepare(
            "INSERT INTO option_trees (id, profile_id, name, language, status, generation_mode)
             VALUES (?, 1, ?, 'nl', 'ready', 'static')"
        )->execute([$topicId, $topic['name']]);

        if ($topic['root_node_id']) {
            $this->syncNode($topicId, (int)$topic['root_node_id'], null, 0);
        }
    }

    private function syncNode(int $treeId, int $oldNodeId, ?int $parentTreeNodeId, int $depth): void
    {
        $stmt = $this->db->prepare("SELECT * FROM options WHERE node_id = ? ORDER BY id ASC");
        $stmt->execute([$oldNodeId]);
        $options = $stmt->fetchAll();

        foreach ($options as $i => $opt) {
            $isLeaf = ($opt['next_node_id'] === null) ? 1 : 0;
            $ins = $this->db->prepare(
                "INSERT INTO tree_nodes (tree_id, parent_id, depth, label, image_url, is_leaf, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->execute([$treeId, $parentTreeNodeId, $depth, $opt['label'], $opt['image_url'], $isLeaf, $i]);
            $newNodeId = (int)$this->db->lastInsertId();

            if (!$isLeaf && $opt['next_node_id']) {
                $this->syncNode($treeId, (int)$opt['next_node_id'], $newNodeId, $depth + 1);
            }
        }
    }
}
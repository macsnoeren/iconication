<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;

/**
 * JSON API voor de Python worker.
 * Alle endpoints vereisen een geldig Bearer token.
 */
class ApiController {
    private \PDO $db;

    public function __construct() {
        header('Content-Type: application/json');
        if (!Config::validateApiKey()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $this->db = Database::getConnection();
    }

    /** GET: geeft alle pending jobs terug. */
    public function pendingJobs(): void {
        try {
            $stmt = $this->db->query(
                "SELECT id, topic, goal, state_json, params_json, job_type FROM ai_jobs WHERE status = 'pending' ORDER BY created_at ASC"
            );
            echo json_encode($stmt->fetchAll());
        } catch (\Exception $e) {
            try {
                $stmt = $this->db->query(
                    "SELECT id, topic, goal, state_json FROM ai_jobs WHERE status = 'pending' ORDER BY created_at ASC"
                );
                $rows = $stmt->fetchAll();
                foreach ($rows as &$r) { $r['job_type'] = 'topic'; $r['params_json'] = null; }
                echo json_encode($rows);
            } catch (\Exception $e2) {
                echo json_encode([]);
            }
        }
    }

    /** GET: geeft alle training-voorbeelden terug als JSON (topic + volledige boom). */
    public function trainingExamples(): void {
        $stmt = $this->db->query(
            "SELECT t.id AS topic_id, t.name AS topic_name, e.notes
             FROM ai_training_examples e
             JOIN topics t ON t.id = e.topic_id"
        );
        $examples = $stmt->fetchAll();

        $result = [];
        foreach ($examples as $ex) {
            // Haal alle nodes + opties op voor dit topic
            $nodeStmt = $this->db->prepare("SELECT id FROM nodes WHERE topic_id = ? ORDER BY id ASC");
            $nodeStmt->execute([$ex['topic_id']]);
            $nodeIds = $nodeStmt->fetchAll(\PDO::FETCH_COLUMN);

            $nodes = [];
            foreach ($nodeIds as $nodeId) {
                $optStmt = $this->db->prepare(
                    "SELECT label, image_hint, next_node_id FROM options WHERE node_id = ? ORDER BY id ASC LIMIT 2"
                );
                $optStmt->execute([$nodeId]);
                $opts = $optStmt->fetchAll();
                if (count($opts) < 2) continue;

                $nodes[] = [
                    'id'       => $nodeId,
                    'option_a' => ['label' => $opts[0]['label'], 'image_hint' => $opts[0]['image_hint'], 'image_url' => $opts[0]['image_url'] ?? '', 'next_node_id' => $opts[0]['next_node_id']],
                    'option_b' => ['label' => $opts[1]['label'], 'image_hint' => $opts[1]['image_hint'], 'image_url' => $opts[1]['image_url'] ?? '', 'next_node_id' => $opts[1]['next_node_id']],
                ];
            }

            $result[] = [
                'topic'  => $ex['topic_name'],
                'notes'  => $ex['notes'],
                'nodes'  => $nodes,
            ];
        }

        echo json_encode($result);
    }

    /** POST: ontvangt het resultaat (of fout) van een verwerkte job. */
    public function submitResult(): void {
        $body = json_decode(file_get_contents('php://input'), true);
        $jobId = isset($body['job_id']) ? (int)$body['job_id'] : 0;

        if ($jobId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'job_id ontbreekt']);
            exit;
        }

        // Controleer of de job bestaat
        $stmt = $this->db->prepare("SELECT id FROM ai_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Job niet gevonden']);
            exit;
        }

        if (!empty($body['error'])) {
            $stmt = $this->db->prepare(
                "UPDATE ai_jobs SET status = 'error', error_message = ?,
                 updated_at = strftime('%s','now') WHERE id = ?"
            );
            $stmt->execute([mb_substr((string)$body['error'], 0, 1000), $jobId]);
            echo json_encode(['ok' => true, 'status' => 'error']);
            return;
        }

        if (empty($body['result'])) {
            http_response_code(400);
            echo json_encode(['error' => 'result of error vereist']);
            exit;
        }

        $resultJson = json_encode($body['result']);
        $stmt = $this->db->prepare(
            "UPDATE ai_jobs SET status = 'done', result_json = ?,
             updated_at = strftime('%s','now') WHERE id = ?"
        );
        $stmt->execute([$resultJson, $jobId]);
        echo json_encode(['ok' => true, 'status' => 'done']);
    }
}

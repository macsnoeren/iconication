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
        $stmt = $this->db->query(
            "SELECT id, topic, goal FROM ai_jobs WHERE status = 'pending' ORDER BY created_at ASC"
        );
        echo json_encode($stmt->fetchAll());
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

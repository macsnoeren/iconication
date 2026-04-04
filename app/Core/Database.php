<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $storageDir = __DIR__ . '/../../storage';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }
            
            $dbPath = $storageDir . '/database.sqlite';
            self::$instance = new PDO("sqlite:$dbPath");
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::initialize();
        }
        return self::$instance;
    }

    private static function initialize(): void {
        $db = self::$instance;
        $db->exec("CREATE TABLE IF NOT EXISTS topics (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, root_node_id INTEGER)");
        $db->exec("CREATE TABLE IF NOT EXISTS nodes (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER)");
        $db->exec("CREATE TABLE IF NOT EXISTS options (id INTEGER PRIMARY KEY AUTOINCREMENT, node_id INTEGER, label TEXT, image_url TEXT, next_node_id INTEGER)");
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT)");

        // Seed data if empty
        $count = $db->query("SELECT COUNT(*) FROM topics")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO topics (name, root_node_id) VALUES ('Eten & Drinken', 1)");
            $db->exec("INSERT INTO nodes (id, topic_id) VALUES (1, 1), (2, 1), (3, 1)");
            
            // Node 1: Hoofdkiezen
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (1, 'Ik wil eten', 'https://cdn-icons-png.flaticon.com/512/1046/1046771.png', 2),
                (1, 'Ik wil drinken', 'https://cdn-icons-png.flaticon.com/512/3100/3100557.png', 3)");
            
            // Node 2: Eten keuzes
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (2, 'Fruit', 'https://cdn-icons-png.flaticon.com/512/3194/3194766.png', NULL),
                (2, 'Brood', 'https://cdn-icons-png.flaticon.com/512/2917/2917623.png', NULL)");
        }
    }
}
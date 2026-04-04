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
        $db->exec("CREATE TABLE IF NOT EXISTS options (id INTEGER PRIMARY KEY AUTOINCREMENT, node_id INTEGER, label TEXT, image_url TEXT, image_hint TEXT, next_node_id INTEGER)");
        // Migreer bestaande databases die image_hint nog niet hebben
        try { $db->exec("ALTER TABLE options ADD COLUMN image_hint TEXT"); } catch (\Exception $e) { /* kolom bestaat al */ }
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT)");

        // Seed data if empty
        $count = $db->query("SELECT COUNT(*) FROM topics")->fetchColumn();
        if ($count == 0) {
            // Topics
            $db->exec("INSERT INTO topics (id, name, root_node_id) VALUES (1, 'Eten & Drinken', 1)");
            $db->exec("INSERT INTO topics (id, name, root_node_id) VALUES (2, 'Gevoelens', 4)");
            $db->exec("INSERT INTO topics (id, name, root_node_id) VALUES (3, 'Behoeften', 6)");
            $db->exec("INSERT INTO topics (id, name, root_node_id) VALUES (4, 'Bibliotheek', 100)"); 

            // Nodes
            $db->exec("INSERT INTO nodes (id, topic_id) VALUES (1, 1), (2, 1), (3, 1), (4, 2), (5, 2), (6, 3), (7, 3)");
            
            // Eten & Drinken
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (1, 'Ik wil eten', 'https://cdn-icons-png.flaticon.com/512/1046/1046771.png', 2),
                (1, 'Ik wil drinken', 'https://cdn-icons-png.flaticon.com/512/3100/3100557.png', 3)");
            
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (2, 'Fruit', 'https://cdn-icons-png.flaticon.com/512/3194/3194766.png', NULL),
                (2, 'Brood', 'https://cdn-icons-png.flaticon.com/512/2917/2917623.png', NULL)");

            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (3, 'Water', 'https://cdn-icons-png.flaticon.com/512/3105/3105807.png', NULL),
                (3, 'Sap', 'https://cdn-icons-png.flaticon.com/512/2442/2442149.png', NULL)");

            // Gevoelens
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (4, 'Blij', 'https://cdn-icons-png.flaticon.com/512/1791/1791330.png', NULL),
                (4, 'Verdrietig', 'https://cdn-icons-png.flaticon.com/512/1791/1791380.png', NULL)");

            // Behoeften
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (6, 'Toilet', 'https://cdn-icons-png.flaticon.com/512/2203/2203551.png', NULL),
                (6, 'Slapen', 'https://cdn-icons-png.flaticon.com/512/3094/3094830.png', NULL)");

            // Bibliotheek - Om de lijst in de beheeromgeving te vullen met veel iconen
            $db->exec("INSERT INTO nodes (id, topic_id) VALUES (100, 4)");
            $db->exec("INSERT INTO options (node_id, label, image_url, next_node_id) VALUES 
                (100, 'Appel', 'https://cdn-icons-png.flaticon.com/512/415/415733.png', NULL),
                (100, 'Banaan', 'https://cdn-icons-png.flaticon.com/512/2909/2909808.png', NULL),
                (100, 'Pizza', 'https://cdn-icons-png.flaticon.com/512/3595/3595455.png', NULL),
                (100, 'Burger', 'https://cdn-icons-png.flaticon.com/512/3075/3075977.png', NULL),
                (100, 'Pasta', 'https://cdn-icons-png.flaticon.com/512/3480/3480618.png', NULL),
                (100, 'Rijst', 'https://cdn-icons-png.flaticon.com/512/3174/3174872.png', NULL),
                (100, 'Melk', 'https://cdn-icons-png.flaticon.com/512/2674/2674486.png', NULL),
                (100, 'Koffie', 'https://cdn-icons-png.flaticon.com/512/3124/3124225.png', NULL),
                (100, 'Thee', 'https://cdn-icons-png.flaticon.com/512/3511/3511413.png', NULL),
                (100, 'Frisdrank', 'https://cdn-icons-png.flaticon.com/512/2722/2722527.png', NULL),
                (100, 'Boos', 'https://cdn-icons-png.flaticon.com/512/1791/1791295.png', NULL),
                (100, 'Lachen', 'https://cdn-icons-png.flaticon.com/512/1791/1791361.png', NULL),
                (100, 'Moe', 'https://cdn-icons-png.flaticon.com/512/1791/1791316.png', NULL),
                (100, 'Bang', 'https://cdn-icons-png.flaticon.com/512/1791/1791350.png', NULL),
                (100, 'Ziek', 'https://cdn-icons-png.flaticon.com/512/2966/2966486.png', NULL),
                (100, 'Koud', 'https://cdn-icons-png.flaticon.com/512/2322/2322765.png', NULL),
                (100, 'Warm', 'https://cdn-icons-png.flaticon.com/512/1684/1684375.png', NULL),
                (100, 'Pijn', 'https://cdn-icons-png.flaticon.com/512/2859/2859735.png', NULL),
                (100, 'Hoofdpijn', 'https://cdn-icons-png.flaticon.com/512/2859/2859735.png', NULL),
                (100, 'Buikpijn', 'https://cdn-icons-png.flaticon.com/512/2545/2545025.png', NULL),
                (100, 'TV kijken', 'https://cdn-icons-png.flaticon.com/512/716/716429.png', NULL),
                (100, 'Spelen', 'https://cdn-icons-png.flaticon.com/512/3082/3082042.png', NULL),
                (100, 'Boek lezen', 'https://cdn-icons-png.flaticon.com/512/2232/2232688.png', NULL),
                (100, 'Muziek', 'https://cdn-icons-png.flaticon.com/512/3293/3293810.png', NULL),
                (100, 'Computer', 'https://cdn-icons-png.flaticon.com/512/3067/3067451.png', NULL),
                (100, 'Tekenen', 'https://cdn-icons-png.flaticon.com/512/2970/2970922.png', NULL),
                (100, 'Wandelen', 'https://cdn-icons-png.flaticon.com/512/3144/3144576.png', NULL),
                (100, 'Fietsen', 'https://cdn-icons-png.flaticon.com/512/2972/2972185.png', NULL),
                (100, 'Douchen', 'https://cdn-icons-png.flaticon.com/512/2950/2950882.png', NULL),
                (100, 'Tanden poetsen', 'https://cdn-icons-png.flaticon.com/512/2553/2553754.png', NULL),
                (100, 'Handen wassen', 'https://cdn-icons-png.flaticon.com/512/2913/2913520.png', NULL),
                (100, 'Aankleden', 'https://cdn-icons-png.flaticon.com/512/3159/3159620.png', NULL),
                (100, 'Jas', 'https://cdn-icons-png.flaticon.com/512/2806/2806045.png', NULL),
                (100, 'Schoenen', 'https://cdn-icons-png.flaticon.com/512/2589/2589906.png', NULL),
                (100, 'Auto', 'https://cdn-icons-png.flaticon.com/512/741/741407.png', NULL),
                (100, 'Bus', 'https://cdn-icons-png.flaticon.com/512/3063/3063822.png', NULL),
                (100, 'School', 'https://cdn-icons-png.flaticon.com/512/167/167707.png', NULL),
                (100, 'Huis', 'https://cdn-icons-png.flaticon.com/512/619/619153.png', NULL),
                (100, 'Bed', 'https://cdn-icons-png.flaticon.com/512/2321/2321385.png', NULL),
                (100, 'Stoel', 'https://cdn-icons-png.flaticon.com/512/2491/2491067.png', NULL),
                (100, 'Tafel', 'https://cdn-icons-png.flaticon.com/512/2560/2560120.png', NULL)");
        }
    }
}
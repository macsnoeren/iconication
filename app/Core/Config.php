<?php
declare(strict_types=1);

namespace App\Core;

class Config {
    /**
     * Geeft de API key terug. Wordt auto-gegenereerd en opgeslagen in
     * storage/api_key.txt als die nog niet bestaat.
     */
    public static function getApiKey(): string {
        $path = __DIR__ . '/../../storage/api_key.txt';
        if (!file_exists($path)) {
            $key = bin2hex(random_bytes(32)); // 64-char hex key
            file_put_contents($path, $key);
            chmod($path, 0600);
        }
        return trim(file_get_contents($path));
    }

    /**
     * Valideert het Bearer token uit de Authorization header.
     */
    public static function validateApiKey(): bool {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            return false;
        }
        $provided = substr($header, 7);
        return hash_equals(self::getApiKey(), $provided);
    }
}

<?php
declare(strict_types=1);

namespace App\Domain\Content;

use App\Core\Database;

class ProfileRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM user_profiles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        return Database::getConnection()
            ->query("SELECT * FROM user_profiles ORDER BY name ASC")
            ->fetchAll();
    }

    public function getSupportLevel(int $profileId): int
    {
        $row = $this->find($profileId);
        return $row ? (int)$row['support_level'] : 2;
    }
}

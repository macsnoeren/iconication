<?php
declare(strict_types=1);

namespace App\Domain\Session;

class Session
{
    public function __construct(
        public readonly string $id,
        public readonly int    $profileId,
        public readonly int    $treeId,
        public string          $state,
        public readonly string $startedAt,
    ) {}

    public function isActive(): bool
    {
        return in_array($this->state, ['EXPLORING', 'CONFIRMING'], true);
    }
}

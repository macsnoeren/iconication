<?php
declare(strict_types=1);

namespace App\Domain\Intent;

class OptionsResult
{
    private function __construct(
        public readonly bool  $isPending,
        public readonly array $options,
        public readonly ?int  $jobId,
    ) {}

    public static function immediate(array $options): self { return new self(false, $options, null); }
    public static function pending(int $jobId): self       { return new self(true,  [],      $jobId); }
}

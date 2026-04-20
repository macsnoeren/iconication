<?php
declare(strict_types=1);

namespace App\Domain\Session;

use App\Domain\Intent\OptionsResult;

class RestoreResult
{
    public function __construct(
        public readonly Session       $session,
        public readonly OptionsResult $options,
        public readonly string        $sentence,
    ) {}
}

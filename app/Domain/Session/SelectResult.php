<?php
declare(strict_types=1);

namespace App\Domain\Session;

use App\Domain\Intent\OptionsResult;

class SelectResult
{
    public function __construct(
        public readonly OptionsResult $options,
        public readonly string        $sentence,
        public readonly string        $newState,
        public readonly ?string       $suggestedMessage,
    ) {}
}

class BackResult
{
    public function __construct(
        public readonly OptionsResult $options,
        public readonly string        $sentence,
    ) {}
}

class RestoreResult
{
    public function __construct(
        public readonly Session       $session,
        public readonly OptionsResult $options,
        public readonly string        $sentence,
    ) {}
}

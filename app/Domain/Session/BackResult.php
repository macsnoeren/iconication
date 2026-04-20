<?php
declare(strict_types=1);

namespace App\Domain\Session;

use App\Domain\Intent\OptionsResult;

class BackResult
{
    public function __construct(
        public readonly OptionsResult $options,
        public readonly string        $sentence,
    ) {}
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeIpLiteralDefaultSingleton
{
    public function __construct(
        public readonly string $note = 'InjectionPoint::getInstance($ip)',
    ) {
    }
}

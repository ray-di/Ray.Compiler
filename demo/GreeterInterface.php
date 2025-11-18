<?php

declare(strict_types=1);

namespace Ray\Compiler\Demo;

interface GreeterInterface
{
    public function greet(string $name): string;
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\Demo;

final class Greeter implements GreeterInterface
{
    public function __construct(private readonly string $greeting = 'Hello')
    {
    }

    public function greet(string $name): string
    {
        return sprintf('%s, %s!', $this->greeting, $name);
    }
}

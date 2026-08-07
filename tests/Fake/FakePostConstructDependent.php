<?php

declare(strict_types=1);

namespace Ray\Compiler;

final class FakePostConstructDependent
{
    public function __construct(public readonly FakePostConstructSingleton $singleton)
    {
    }
}

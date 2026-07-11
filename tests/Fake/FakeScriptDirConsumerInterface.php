<?php

declare(strict_types=1);

namespace Ray\Compiler;

interface FakeScriptDirConsumerInterface
{
    public function getScriptDir(): string;
}

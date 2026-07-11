<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Annotation\ScriptDir;

class FakeScriptDirConstructorConsumer implements FakeScriptDirConsumerInterface
{
    public function __construct(#[ScriptDir] private string $scriptDir)
    {
    }

    public function getScriptDir(): string
    {
        return $this->scriptDir;
    }
}

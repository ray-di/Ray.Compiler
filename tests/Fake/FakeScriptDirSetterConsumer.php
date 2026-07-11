<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Annotation\ScriptDir;
use Ray\Di\Di\Inject;

class FakeScriptDirSetterConsumer implements FakeScriptDirConsumerInterface
{
    private string $scriptDir = '';

    #[Inject]
    public function setScriptDir(#[ScriptDir] string $scriptDir): void
    {
        $this->scriptDir = $scriptDir;
    }

    public function getScriptDir(): string
    {
        return $this->scriptDir;
    }
}

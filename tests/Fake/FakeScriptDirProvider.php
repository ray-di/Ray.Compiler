<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Annotation\ScriptDir;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<FakeScriptDirConsumerInterface> */
class FakeScriptDirProvider implements ProviderInterface
{
    public function __construct(#[ScriptDir] private string $scriptDir)
    {
    }

    public function get(): FakeScriptDirConsumerInterface
    {
        return new FakeScriptDirConstructorConsumer($this->scriptDir);
    }
}

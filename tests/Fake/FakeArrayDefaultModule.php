<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeArrayDefaultModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(FakeClassWithArrayDefault::class);
    }
}

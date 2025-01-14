<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Di\AbstractModule;

class FakeLazyModule extends AbstractModule
{
    public function configure(): void
    {
        $this->install(new FakeCarModule());
        $this->install(new FakeLoggerModule());
        $this->install(new FakeToBindSingletonModule());
        $this->install(new FakeMultiBindingsModule());
    }
}

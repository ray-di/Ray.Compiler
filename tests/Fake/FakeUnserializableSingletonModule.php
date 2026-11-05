<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class FakeUnserializableSingletonModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeUnserializableSingleton::class)->in(Scope::SINGLETON);
    }
}

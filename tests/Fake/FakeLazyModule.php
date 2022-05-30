<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeLazyModule implements LazyModuleInterface
{
    public function __invoke(): AbstractModule
    {
        $module = new FakeCarModule();
        $module->install(new FakeLoggerModule());
        $module->install(new FakeToBindSingletonModule());

        return $module;
    }
}

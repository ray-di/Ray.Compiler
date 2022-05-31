<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Di\AbstractModule;

class FakeLazyModule implements LazyModuleInterface
{
    public function __invoke(): AbstractModule
    {
        $module = new FakeCarModule();
        $module->install(new FakeLoggerModule());
        $module->install(new FakeToBindSingletonModule());
        $module->install(new FakeMultiBindingsModule());

        return $module;
    }
}

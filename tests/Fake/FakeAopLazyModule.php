<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

final class FakeAopLazyModule implements LazyModuleInterface
{
    public function __invoke(): AbstractModule
    {
        $module = new FakeAopModule();
        $module->install(new FakeProdModule());

        return $module;
    }
}

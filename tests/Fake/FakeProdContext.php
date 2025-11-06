<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

final class FakeProdContext extends AbstractInjectorContext
{
    function __invoke(): AbstractModule
    {
        $module = new FakeToBindPrototypeModule();
        $module->install(new DiCompileModule(true));

        return $module;
    }
}

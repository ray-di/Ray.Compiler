<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

final class FakeTestContext extends AbstractInjectorContext
{
    function __invoke(): AbstractModule
    {
        return new FakeToBindPrototypeModule();
    }
}

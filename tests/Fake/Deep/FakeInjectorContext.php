<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;

use Ray\Compiler\AbstractInjectorContext;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class FakeInjectorContext extends AbstractInjectorContext
{
    public function __invoke(): AbstractModule
    {
        return new FakeDepModule();
    }
}

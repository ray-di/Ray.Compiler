<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;

use Ray\Compiler\AbstractInjectorContext;
use Ray\Di\AbstractModule;

final class FakeScriptInjectorContext extends AbstractInjectorContext
{
    public function __invoke(): AbstractModule
    {
        return new FakeDepModule();
    }
}

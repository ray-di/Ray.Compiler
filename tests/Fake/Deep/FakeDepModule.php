<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class FakeDepModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeDep::class)->in(Scope::SINGLETON);
    }
}

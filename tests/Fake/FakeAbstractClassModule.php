<?php

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeAbstractClassModule extends AbstractModule
{
    protected function configure() : void
    {
        $this->bind(FakeAbstractClass::class)->toProvider(FakeExtendedProvider::class);
    }
}

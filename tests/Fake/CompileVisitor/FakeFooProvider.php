<?php

namespace Ray\Compiler\CompileVisitor;

use Ray\Di\ProviderInterface;

class FakeFooProvider implements ProviderInterface
{
    public function get(): FakeFooInterface
    {
        return new FakeFoo();
    }
}

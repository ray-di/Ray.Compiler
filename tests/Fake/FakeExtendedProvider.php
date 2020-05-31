<?php

namespace Ray\Compiler;

use Ray\Di\ProviderInterface;

class FakeExtendedProvider implements ProviderInterface
{
    public function get()
    {
        return new FakeExtendedClass;
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\ProviderInterface;

class FakeQualifierPathsProvider implements ProviderInterface
{
    public function get()
    {
        return new FakeEngine();
    }
}

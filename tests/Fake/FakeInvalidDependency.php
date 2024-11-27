<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Bind;
use Ray\Di\Container;
use Ray\Di\DependencyInterface;

class FakeInvalidDependency implements DependencyInterface
{
    public function inject(Container $container)
    {
        unset($container);
    }

    public function register(array &$container, Bind $bind)
    {
        unset($container, $bind);
    }

    public function setScope($scope)
    {
        unset($scope)
    }

    public function __toString()
    {
    }
}

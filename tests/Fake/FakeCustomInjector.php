<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake;

use LogicException;
use Ray\Di\InjectorInterface;

/**
 * Fake custom injector for testing override behavior
 */
final class FakeCustomInjector implements InjectorInterface
{
    /**
     * @param class-string $interface
     * @param string       $name
     *
     * @return never
     */
    public function getInstance($interface, $name = '')
    {
        throw new LogicException('This injector should be overridden by CompilerModule');
    }
}

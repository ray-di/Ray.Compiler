<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

/**
 * Factory class for creating a lazy module
 *
 * Lazymodule is required to create CompileInjector. This utility class is useful when creating a module
 * from a variable that can be called for testing purposes.
 *
 * Please do not use this in production code. Instead of using this class, please create a class that implements LazyModuleInterface.
 */
final class LazyModule
{
    /**
     * Create a lazy module from a callable that returns a module
     */
    public static function getInstance(callable $callable): LazyModuleInterface
    {
        return new class ($callable) implements LazyModuleInterface {
            /** @var callable */
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function __invoke(): AbstractModule
            {
                return ($this->callable)();
            }
        };
    }
}

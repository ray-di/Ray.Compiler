<?php

declare(strict_types=1);

namespace Ray\Compiler;

use LogicException;
use Ray\Di\AbstractModule;

/** @deprecated  */
final class OnDemandCompiler
{
    public function __construct(ScriptInjector $injector, string $scriptDir, AbstractModule $module)
    {
        unset($injector, $scriptDir, $module);
    }

    public function __invoke(string $dependencyIndex): void
    {
        throw new LogicException($dependencyIndex);
    }
}

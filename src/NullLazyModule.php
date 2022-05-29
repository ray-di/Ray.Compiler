<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\NullModule;

final class NullLazyModule implements LazyModuleInterface
{
    public function __invoke(): AbstractModule
    {
        return new NullModule();
    }
}

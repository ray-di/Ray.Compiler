<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Ray\Di\AbstractModule;
use Ray\Di\NullCache;

final class FakeStgContext extends AbstractInjectorContext
{
    function __invoke(): AbstractModule
    {
        $module = new FakeToBindPrototypeModule();
        $module->install(new DiCompileModule(true));
        $module->install(new FakeNullBindingModule());

        return $module;
    }

    function getCache(): CacheProvider
    {
        return new NullCache();
    }
}

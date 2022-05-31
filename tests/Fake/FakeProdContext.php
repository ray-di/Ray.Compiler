<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Ray\Di\AbstractModule;
use Ray\Di\NullCache;

final class FakeProdContext extends AbstractInjectorContext
{
    function __invoke(): AbstractModule
    {
        $module = new FakeToBindPrototypeModule();
        $module->install(new DiCompileModule(true));

        return $module;
    }

    function getCache(): CacheProvider
    {
        return new NullCache();
    }
}

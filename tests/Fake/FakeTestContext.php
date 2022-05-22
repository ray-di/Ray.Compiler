<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Ray\Di\AbstractModule;
use Ray\Di\NullCache;

final class FakeTestContext extends AbstractInjectorContext
{
    function getModule(): AbstractModule
    {
        return new FakeToBindPrototypeModule();
    }

    function getCache(): CacheProvider
    {
        return new NullCache();
    }
}

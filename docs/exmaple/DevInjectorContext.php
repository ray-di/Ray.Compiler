<?php

declare(strict_types=1);

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\CacheProvider;
use Ray\Compiler\AbstractInjectorContext;
use Ray\Compiler\DiCompileModule;
use Ray\Compiler\FakeCarModule;
use Ray\Di\AbstractModule;
use Ray\Di\NullCache;

final class DevInjectorContext extends AbstractInjectorContext
{
    public function __invoke(): AbstractModule
    {
        $module = new AppModule();

        // Override test-only binding
        $module->override(new DevModule());

        return $module;
    }

    public function getCache(): CacheProvider
    {
        return new NullCache();
    }
}

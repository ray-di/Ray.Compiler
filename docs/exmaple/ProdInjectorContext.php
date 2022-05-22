<?php

declare(strict_types=1);

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\CacheProvider;
use Ray\Compiler\AbstractInjectorContext;
use Ray\Compiler\DiCompileModule;
use Ray\Compiler\FakeCarModule;
use Ray\Di\AbstractModule;

final class ProdInjectorContext extends AbstractInjectorContext
{
    public function getModule(): AbstractModule
    {
        $module = new FakeCarModule();
        $module->override(new DiCompileModule(true));

        return $module;
    }

    public function getCache(): CacheProvider
    {
        return new ApcuCache();
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;


use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Ray\Compiler\AbstractInjectorContext;
use Ray\Compiler\Annotation\Compile;
use Ray\Compiler\DiCompileModule;
use Ray\Di\AbstractModule;
use Ray\Di\NullCache;
use Ray\Di\Scope;

final class FakeScriptInjectorContext extends AbstractInjectorContext
{
    public function getModule(): AbstractModule
    {
        return new FakeDepModule();
    }

    public function getCache(): CacheProvider
    {
       return new FilesystemCache('/tmp');
    }
}

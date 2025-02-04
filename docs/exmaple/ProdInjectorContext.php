<?php

declare(strict_types=1);

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\CacheProvider;
use Ray\Compiler\AbstractInjectorContext;
use Ray\Compiler\DiCompileModule;
use Ray\Di\AbstractModule;

final class ProdInjectorContext extends AbstractInjectorContext
{
    public function __invoke(): AbstractModule
    {
        $module = new AppModule();

        // Compile the binding. If dependencies cannot be resolved, an exception is raised at compile time.
        $module->override(new DiCompileModule(true));

        return $module;
    }

    public function getCache(): CacheProvider
    {
        if (! class_exists(ApcuCache::class)) {
            throw new \RuntimeException('doctrine/cache ^1.0 is required for ProdInjectorContext.');
        }

        return new ApcuCache(); // @phpstan-ignore-line
    }
}

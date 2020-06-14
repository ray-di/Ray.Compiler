<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Ray\Di\InjectorInterface;

final class CachedInjectorFactory
{
    /**
     * @var array<string, CacheProvider>
     */
    private static $cache = [];

    private function __construct()
    {
    }

    /**
     * @param callable():\Ray\Di\AbstractModule $modules
     * @param array<class-string>               $savedSingletons
     */
    public static function getInstance(string $injectorId, string $scriptDir, callable $modules, CacheProvider $cache = null, array $savedSingletons = []) : InjectorInterface
    {
        if (! isset(self::$cache[$injectorId])) {
            self::$cache[$injectorId] = $cache ?? new ArrayCache;
        }
        $cache = self::$cache[$injectorId];
        $cache->setNamespace($injectorId);
        /** @var ?InjectorInterface $cachedInjector */
        $cachedInjector = $cache->fetch(InjectorInterface::class);
        if ($cachedInjector instanceof InjectorInterface) {
            return $cachedInjector;
        }
        $injector = InjectorFactory::getInstance($modules, $scriptDir);
        self::saveSingletons($injector, $savedSingletons);
        $cache->save(InjectorInterface::class, $injector);

        return $injector;
    }

    /**
     * @param array<class-string> $savedSingletons
     */
    private static function saveSingletons(InjectorInterface $injector, array $savedSingletons) : void
    {
        foreach ($savedSingletons as $singleton) {
            $injector->getInstance($singleton);
        }
    }
}

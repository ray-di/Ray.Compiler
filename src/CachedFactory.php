<?php

declare(strict_types=1);

namespace Ray\Compiler;

use function crc32;
use Doctrine\Common\Cache\Cache;
use Ray\Di\InjectorInterface;

/**
 * @psalm-immutable
 */
final class CachedFactory
{
    /**
     * @var array<InjectorInterface>
     */
    private static $instances;

    private function __construct()
    {
    }

    /**
     * @param class-string|callable():AbstractModule $initialModule
     * @param array<class-string<AbstractModule>>    $contextModules
     * @param array<class-string>                    $savedSingletons
     */
    public static function getInstance($initialModule, array $contextModules, string $scriptDir, Cache $cache, array $savedSingletons = []) : InjectorInterface
    {
        $injectorId = crc32($scriptDir);
        if (isset(self::$instances[$injectorId])) {
            return self::$instances[$injectorId];
        }
        /** @var ?InjectorInterface $cachedInjector */
        $cachedInjector = $cache->fetch(InjectorInterface::class);
        $injector = $cachedInjector instanceof InjectorInterface ? $cachedInjector : InjectorFactory::getInstance($initialModule, $contextModules, $scriptDir);
        self::saveSingletons($injector, $savedSingletons);
        self::$instances[$injectorId] = $injector;

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

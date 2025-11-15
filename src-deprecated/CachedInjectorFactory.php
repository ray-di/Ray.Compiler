<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;

use function assert;
use function serialize;
use function unserialize;

/**
 * @deprecated This class is deprecated. Use InjectorFactory directly instead.
 *             The cache functionality has been removed as doctrine/cache is abandoned.
 *
 * @psalm-import-type ScriptDir from Types
 * @psalm-import-type SavedSingletons from Types
 */
final class CachedInjectorFactory
{
    /** @var array<string, string> */
    private static $injectors = [];

    /**
     * @param non-empty-string           $scriptDir
     * @param callable(): AbstractModule $modules
     * @param mixed                      $cache           Deprecated parameter (no longer used)
     * @param SavedSingletons            $savedSingletons
     *
     * @deprecated The $cache parameter is deprecated. doctrine/cache has been abandoned.
     *             Pass null or omit this parameter.
     */
    public static function getInstance(string $injectorId, string $scriptDir, callable $modules, mixed $cache = null, array $savedSingletons = []): InjectorInterface
    {
        if (isset(self::$injectors[$injectorId])) {
            /** @noinspection UnserializeExploitsInspection */
            $injector = unserialize(self::$injectors[$injectorId]);
            assert($injector instanceof InjectorInterface);

            return $injector;
        }

        // $cache parameter is ignored for backward compatibility
        $injector = self::getInjector($modules, $scriptDir, $savedSingletons);
        self::$injectors[$injectorId] = serialize($injector);

        return $injector;
    }

    /**
     * @param non-empty-string           $scriptDir
     * @param callable(): AbstractModule $modules
     * @param SavedSingletons            $savedSingletons
     */
    public static function getOverrideInstance(
        string $scriptDir,
        callable $modules,
        AbstractModule $overrideModule,
        array $savedSingletons = []
    ): InjectorInterface {
        return self::getInjector($modules, $scriptDir, $savedSingletons, $overrideModule);
    }

    /**
     * @param callable(): AbstractModule $modules
     * @param non-empty-string           $scriptDir
     * @param SavedSingletons            $savedSingletons
     */
    private static function getInjector(callable $modules, string $scriptDir, array $savedSingletons, ?AbstractModule $module = null): InjectorInterface
    {
        if ($module !== null) {
            $modules = new OverrideLazyModule($modules, $module);
        }

        $injector = InjectorFactory::getInstance($modules, $scriptDir);
        foreach ($savedSingletons as $singleton) {
            $injector->getInstance($singleton);
        }

        return $injector;
    }
}

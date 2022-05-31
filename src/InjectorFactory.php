<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Annotation\Compile;
use Ray\Di\AbstractModule;
use Ray\Di\Exception\Unbound;
use Ray\Di\Injector as RayInjector;
use Ray\Di\InjectorInterface;

use function is_dir;
use function mkdir;

/**
 * @psalm-immutable
 */
final class InjectorFactory
{
    private function __construct()
    {
    }

    /**
     * @param callable(): AbstractModule $modules
     */
    public static function getInstance(callable $modules, string $scriptDir): InjectorInterface
    {
        ! is_dir($scriptDir) && ! @mkdir($scriptDir) && ! is_dir($scriptDir);
        $module = $modules();
        $rayInjector = new RayInjector($module, $scriptDir);
        $isProd = false;
        try {
            $isProd = $rayInjector->getInstance('', Compile::class);
        } catch (Unbound $e) {
        }

        if (! $isProd) {
            return $rayInjector;
        }

        if ($modules instanceof LazyModuleInterface) {
            return self::getCompileInjector($scriptDir, $modules);
        }

        return self::getScriptInjector($scriptDir, $module);
    }

    private static function getScriptInjector(string $scriptDir, AbstractModule $module): ScriptInjector
    {
        return new ScriptInjector($scriptDir, static function () use ($scriptDir, $module) {
            return new ScriptinjectorModule($scriptDir, $module);
        });
    }

    private static function getCompileInjector(string $scriptDIr, LazyModuleInterface $module): CompileInjector
    {
        return new CompileInjector($scriptDIr, $module);
    }
}

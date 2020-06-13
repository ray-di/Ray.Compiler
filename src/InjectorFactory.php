<?php

declare(strict_types=1);

namespace Ray\Compiler;

use function is_callable;
use Ray\Compiler\Annotation\Compile;
use Ray\Di\AbstractModule;
use Ray\Di\AssistedModule;
use Ray\Di\Exception\Unbound;
use Ray\Di\Injector as RayInjector;
use Ray\Di\InjectorInterface;

/**
 * @psalm-immutable
 */
final class InjectorFactory
{
    private function __construct()
    {
    }

    /**
     * @param class-string|callable():AbstractModule $initialModule
     * @param array<class-string<AbstractModule>>    $contextModules
     */
    public static function getInstance($initialModule, array $contextModules, string $scriptDir) : InjectorInterface
    {
        ! is_dir($scriptDir) && ! @mkdir($scriptDir) && ! is_dir($scriptDir);
        $module = is_callable($initialModule) ? $initialModule() : new $initialModule;
        $module->override(new AssistedModule);
        foreach ($contextModules as $contextModule) {
            /** @var $module AbstractModule */
            $module->override(new $contextModule);
        }
        $rayInjector = new RayInjector($module, $scriptDir);
        /** @var bool $isProd */
        $isProd = false;
        try {
            $isProd = $rayInjector->getInstance('', Compile::class);
        } catch (Unbound $e) {
        }

        return $isProd ? self::getScriptInjector($scriptDir, $module) : $rayInjector;
    }

    private static function getScriptInjector(string $scriptDir, AbstractModule $module) : ScriptInjector
    {
        return new ScriptInjector($scriptDir, function () use ($scriptDir, $module) {
            return new ScriptinjectorModule($scriptDir, $module);
        });
    }
}

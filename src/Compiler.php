<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\CompileLockFailed;
use Ray\Compiler\Exception\SingletonRequiresInjectionPoint;
use Ray\Di\AbstractModule;
use Ray\Di\AcceptInterface;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\ContainerFactory;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;

use function assert;
use function fclose;
use function flock;
use function fopen;
use function in_array;
use function is_string;
use function json_encode;

use const LOCK_EX;
use const LOCK_UN;

/**
 *  Module Compiler
 *
 *  Compiles module bindings into PHP files for CompiledInjector.
 *  The compilation process includes:
 *  - Acquiring a file lock to ensure thread safety
 *  - Converting dependencies into PHP scripts using CompileVisitor
 *  - Saving compiled scripts to the target directory
 *
 * @psalm-import-type ScriptDir from Types
 */
final class Compiler
{
    /** Indexes resolvable only inside a caller context (AOP interception) */
    private const CONTEXT_SENSITIVE = ['Ray\Aop\MethodInvocation-'];

    /**
     * Compiles a given module into Scripts
     *
     * @param ScriptDir $scriptDir
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) // @phpstan-ignore-line
     */
    public function compile(AbstractModule $module, string $scriptDir): Scripts
    {
        $module->override(new CompilerModule($scriptDir));

        $fp = fopen($scriptDir . '/compile.lock', 'a+');
        if ($fp === false || ! flock($fp, LOCK_EX)) {
            // @codeCoverageIgnoreStart
            if ($fp !== false) {
                fclose($fp);
            }

            throw new CompileLockFailed($scriptDir);
            // @codeCoverageIgnoreEnd
        }

        $scripts = new Scripts();
        $container = (new ContainerFactory())($module, $scriptDir);
        // Compile dependencies
        $compileVisitor = new CompileVisitor($container);
        $singletonIndexes = [];
        $container->map(static function (DependencyInterface $dependency, string $key) use ($scripts, $compileVisitor, &$singletonIndexes): DependencyInterface {
            assert($dependency instanceof AcceptInterface);
            if ($key === InstanceScript::RAY_DI_SCRIPT_DIR) {
                $scripts->add($key, 'return __DIR__;');

                return $dependency;
            }

            $script = $dependency->accept($compileVisitor);
            assert(is_string($script));
            $injectionPointUsed = $compileVisitor->consumeInjectionPointUsage();
            if ($injectionPointUsed && self::isSingletonDependency($dependency)) {
                throw new SingletonRequiresInjectionPoint($key);
            }

            $scripts->add($key, $script);
            if (self::isWarmupCandidate($dependency, $key)) {
                $singletonIndexes[] = $key;
            }

            return $dependency;
        });
        $scripts->save($scriptDir);
        (new FilePutContents())($scriptDir . '/' . CompiledInjector::SINGLETONS_FILE, (string) json_encode($singletonIndexes));
        flock($fp, LOCK_UN);
        fclose($fp);

        return $scripts;
    }

    private static function isWarmupCandidate(DependencyInterface $dependency, string $key): bool
    {
        if (in_array($key, self::CONTEXT_SENSITIVE, true)) {
            return false;
        }

        return self::isSingletonDependency($dependency);
    }

    private static function isSingletonDependency(DependencyInterface $dependency): bool
    {
        return ($dependency instanceof Dependency || $dependency instanceof DependencyProvider) && $dependency->isSingleton();
    }
}

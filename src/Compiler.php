<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Compiler as AopCompiler;
use Ray\Compiler\Exception\CompileLockFailed;
use Ray\Di\AbstractModule;
use Ray\Di\AcceptInterface;
use Ray\Di\DependencyInterface;

use function assert;
use function fclose;
use function flock;
use function fopen;
use function is_string;

use const LOCK_EX;
use const LOCK_UN;

final class Compiler
{
    /**
     * Compiles a given module into Scripts
     */
    public function compile(AbstractModule $module, string $scriptDir): Scripts
    {
        // Lock
        $fp = fopen($scriptDir . '/compile.lock', 'a+');
        if ($fp === false || !flock($fp, LOCK_EX)) {
            // @CoverageIgnoreStart
            throw new CompileLockFailed($scriptDir);
            // @CoverageIgnoreEnd
        }

        $scripts = new Scripts();
        $container = (new InstallBuiltinModule())($module)->getContainer();
        // Compile null objects
        (new CompileNullObject())($container, $scriptDir);
        // Compile aspects
        $container->weaveAspects(new AopCompiler($scriptDir));
        // Compile dependencies
        $compileVisitor = new CompileVisitor($container);
        $container->map(static function (DependencyInterface $dependency, string $key) use ($scripts, $compileVisitor): DependencyInterface {
            assert($dependency instanceof AcceptInterface);
            $script = $dependency->accept($compileVisitor);
            assert(is_string($script));
            $scripts->add($key, $script);

            return $dependency;
        });
        $scripts->save($scriptDir);
        // Unlock
        flock($fp, LOCK_UN);
        fclose($fp);

        return $scripts;
    }
}

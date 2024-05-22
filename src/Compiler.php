<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Compiler as AopCompiler;
use Ray\Di\AbstractModule;
use Ray\Di\DependencyInterface;

final class Compiler
{
    /**
     * Compiles a given module into Scripts
     */
    public function compile(AbstractModule $module, string $scriptDir): Scripts
    {
        $scripts = new Scripts();
        $container = (new InstallBuiltinModule())($module)->getContainer();
        // Weave aspects
        $container->weaveAspects(new AopCompiler($scriptDir));
        // Compile NullObject
        (new CompileNullObject())($container, $scriptDir);
        // Compile dependencies
        $compileVisitor = new CompileVisitor($container);$container->map(static function (DependencyInterface $dependency, string $key) use ($scripts, $compileVisitor): DependencyInterface {
            $script = $dependency->accept($compileVisitor);
            $scripts->add($key, $script);

            return $dependency;
        });
        $scripts->save($scriptDir);

        return $scripts;
    }
}

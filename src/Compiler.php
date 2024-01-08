<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\DependencyInterface;

final class Compiler
{
    /**
     * Compiles a given module into Scripts
     */
    public function compile(AbstractModule $module): Scripts
    {
        $scripts = new Scripts();
        $container = $module->getContainer();
        $compileVisitor = new CompileVisitor($container);
        $container->map(static function (DependencyInterface $dependency, string $key) use ($scripts, $compileVisitor) {
            $script = $dependency->accept($compileVisitor);
            $scripts->add($key, $script);
        });

        return $scripts;
    }
}

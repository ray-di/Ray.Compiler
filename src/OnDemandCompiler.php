<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use Ray\Aop\Compiler;
use Ray\Compiler\Exception\ClassNotFound;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Bind;
use Ray\Di\Container;

final class OnDemandCompiler
{
    /**
     * @var string
     */
    private $scriptDir;

    /**
     * @var ScriptInjector
     */
    private $injector;

    public function __construct(ScriptInjector $injector, string $sctiptDir)
    {
        $this->scriptDir = $sctiptDir;
        $this->injector = $injector;
    }

    /**
     * Compile depdency on demand
     */
    public function __invoke(string $dependencyIndex) : void
    {
        list($class) = \explode('-', $dependencyIndex);
        if (! \class_exists($class)) {
            $e = new ClassNotFound($dependencyIndex);
            throw new Unbound($dependencyIndex, 0, $e);
        }
        $container = new Container();
        new Bind($container, $class);
        /** @var \Ray\Di\Dependency $dependency */
        $dependency = $container->getContainer()[$dependencyIndex];
        $pointCuts = $this->loadPointcuts();
        if ($pointCuts) {
            $dependency->weaveAspects(new Compiler($this->scriptDir), $pointCuts);
        }
        $code = (new DependencyCode(new Container, $this->injector))->getCode($dependency);
        (new DependencySaver($this->scriptDir))->__invoke($dependencyIndex, $code);
    }

    /**
     * @return array|false
     */
    private function loadPointcuts()
    {
        $pointcuts = $this->scriptDir . DiCompiler::POINT_CUT;
        if (! \file_exists($pointcuts)) {
            return false;
        }

        return  \unserialize(\file_get_contents($pointcuts));
    }
}

<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use Ray\Aop\Compiler;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\AbstractModule;
use Ray\Di\Bind;
use Ray\Di\Exception\NotFound;

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

    /**
     * @var AbstractModule
     */
    private $module;

    public function __construct(ScriptInjector $injector, string $sctiptDir, AbstractModule $module)
    {
        $this->scriptDir = $sctiptDir;
        $this->injector = $injector;
        $this->module = $module;
    }

    /**
     * Compile depdency on demand
     */
    public function __invoke(string $dependencyIndex) : void
    {
        list($class) = \explode('-', $dependencyIndex);
        $containerObject = $this->module->getContainer();
        try {
            new Bind($containerObject, $class);
        } catch (NotFound $e) {
            throw new Unbound($dependencyIndex, 0, $e);
        }
        $containerArray = $containerObject->getContainer();
        /* @var \Ray\Di\Dependency $dependency */
        if (! isset($containerArray[$dependencyIndex])) {
            throw new Unbound($dependencyIndex, 0);
        }
        $dependency = $containerArray[$dependencyIndex];
        $pointCuts = $this->loadPointcuts();
        if ($pointCuts) {
            $dependency->weaveAspects(new Compiler($this->scriptDir), $pointCuts);
        }
        $code = (new DependencyCode($containerObject, $this->injector))->getCode($dependency);
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

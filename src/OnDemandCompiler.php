<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Compiler;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\AbstractModule;
use Ray\Di\Bind;
use Ray\Di\Dependency;
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
    public function __invoke(string $dependencyIndex)
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
        /** @var Dependency $dependency */
        $dependency = $containerArray[$dependencyIndex];
        $pointCuts = $this->loadPointcuts();
        if ($dependency instanceof Dependency && \is_array($pointCuts)) {
            $dependency->weaveAspects(new Compiler($this->scriptDir), $pointCuts);
        }
        $code = (new DependencyCode($containerObject, $this->injector))->getCode($dependency);
        (new DependencySaver($this->scriptDir))($dependencyIndex, $code);
    }

    /**
     * @return array|false
     */
    private function loadPointcuts()
    {
        $pointcutsFile = $this->scriptDir . ScriptInjector::AOP;
        if (! \file_exists($pointcutsFile)) {
            return false;
        }
        $pointcuts = \file_get_contents($pointcutsFile);
        if (\is_bool($pointcuts)) {
            throw new \RuntimeException; // @codeCoverageIgnore
        }

        return  \unserialize($pointcuts, ['allowed_classes' => true]);
    }
}

<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use Ray\Di\Argument;
use Ray\Di\Container;
use Ray\Di\InjectorInterface;
use Ray\Di\Instance;
use Ray\Di\Name;

final class FactoryCompiler
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var InjectorInterface
     */
    private $injector;

    /**
     * @var DependencyCompiler
     */
    private $compiler;

    /**
     * @var OnDemandCompiler
     */
    private $onDemandDependencyCompiler;

    /**
     * @var FunctionCompiler
     */
    private $functionCompiler;

    public function __construct(
        Container $container,
        Normalizer $normalizer,
        DependencyCompiler $compiler,
        InjectorInterface $injector = null
    ) {
        $this->container = $container;
        $this->normalizer = $normalizer;
        $this->injector = $injector;
        $this->onDemandDependencyCompiler = new OnDemandCompiler($normalizer, $this, $injector);
        $this->functionCompiler = new FunctionCompiler($container, new PrivateProperty);
    }

    /**
     * @param string $class
     * @param array  $arguments
     * @param array  $setterMethods
     * @param string $postConstruct
     *
     * @return Node[]
     */
    public function getFactoryCode($class, array $arguments, array $setterMethods, $postConstruct)
    {
        $node = [];
        $instance = new Expr\Variable('instance');
        // constructor injection
        $constructorInjection = $this->constructorInjection($class, $arguments);
        $node[] = new Expr\Assign($instance, $constructorInjection);
        $setters = $this->onDemandDependencyCompiler->setterInjection($instance, $setterMethods);
        foreach ($setters as $setter) {
            $node[] = $setter;
        }
        if ($postConstruct) {
            $node[] = $this->onDemandDependencyCompiler->postConstruct($instance, $postConstruct);
        }

        return $node;
    }

    /**
     * Return method argument code
     *
     * @param Argument $argument
     *
     * @return Expr|Expr\FuncCall
     */
    public function getArgStmt(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        if ($dependencyIndex === 'Ray\Di\InjectionPointInterface-' . Name::ANY) {
            return $this->getInjectionPoint();
        }
        $hasDependency = isset($this->container->getContainer()[$dependencyIndex]);
        if (! $hasDependency) {
            return $this->onDemandDependencyCompiler->getOnDemandDependency($argument);
        }
        $dependency = $this->container->getContainer()[$dependencyIndex];
        if ($dependency instanceof Instance) {
            return $this->normalizer->__invoke($dependency->value);
        }

        return $this->functionCompiler->__invoke($argument, $dependency);
    }

    /**
     * @param string $class
     * @param array  $arguments
     *
     * @return Expr\New_
     */
    private function constructorInjection($class, array $arguments = [])
    {
        /* @var $arguments Argument[] */
        $args = [];
        foreach ($arguments as $argument) {
            //            $argument = $argument->isDefaultAvailable() ? $argument->getDefaultValue() : $argument;
            $args[] = $this->getArgStmt($argument);
        }
        $constructor = new Expr\New_(new Node\Name\FullyQualified($class), $args);

        return $constructor;
    }

    /**
     * Return "$injection_point()"
     *
     * @return Expr\FuncCall
     */
    private function getInjectionPoint()
    {
        return new Expr\FuncCall(new Expr\Variable('injection_point'));
    }
}

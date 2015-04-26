<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Ray\Di\Argument;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;
use Ray\Di\Instance;

final class DependencyCompiler
{
    /**
     * @var \PhpParser\BuilderFactory
     */
    private $factory;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Normalizer
     */
    private $normalizer;

    public function __construct(Container $container)
    {
        $this->factory = new \PhpParser\BuilderFactory;
        $this->container = $container;
        $this->normalizer = new Normalizer;
    }

    /**
     * @param DependencyInterface $dependency
     *
     * @return Code
     */
    public function compile(DependencyInterface $dependency)
    {
        if ($dependency instanceof Dependency) {
            return $this->compileDependency($dependency);
        } elseif ($dependency instanceof Instance) {
            return $this->compileInstance($dependency);
        } elseif ($dependency instanceof DependencyProvider) {
            return $this->compileDependencyProvider($dependency);
        }

        throw new \DomainException(get_class($dependency));
    }

    private function compileInstance(Instance $instance)
    {
        $node = $this->normalizer->normalizeValue($instance->value);

        return new Code(new Node\Stmt\Return_($node));
    }

    private function compileDependency(Dependency $dependency)
    {
        $node = $this->getFactoryNode($dependency);
        $this->getAopCode($dependency, $node);
        $node[] = new Node\Stmt\Return_(new Node\Expr\Variable('instance'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();

        return new Code($node);
    }

    private function compileDependencyProvider(DependencyProvider $provider)
    {
        $dependency = $this->getPrivateProperty($provider, 'dependency');
        $node = $this->getFactoryNode($dependency);
        $node[] = new Stmt\Return_(new Expr\MethodCall(new Expr\Variable('instance'), 'get'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();

        return new Code($node);
    }

    private function getFactoryNode(DependencyInterface $dependency)
    {
        $newInstance = $this->getPrivateProperty($dependency, 'newInstance');
        // class name
        $class = $this->getPrivateProperty($newInstance, 'class');
        $setterMethods = (array) $this->getPrivateProperty($this->getPrivateProperty($newInstance, 'setterMethods'), 'setterMethods');
        $arguments = (array) $this->getPrivateProperty($this->getPrivateProperty($newInstance, 'arguments'), 'arguments');
        $postConstruct = $this->getPrivateProperty($dependency, 'postConstruct');
        $isSingleton = $this->getPrivateProperty($dependency, 'isSingleton');

        return $this->getFactoryCode($class, $arguments, $setterMethods, $postConstruct, $isSingleton);
    }

    /**
     * @param string $class
     * @param array  $arguments
     * @param array  $setterMethods
     * @param string $postConstruct
     *
     * @return Node[]
     */
    private function getFactoryCode($class, array $arguments, array $setterMethods, $postConstruct, $isSingleton)
    {
        $node = [];
        $instance = new Expr\Variable('instance');
        // constructor injection
        $constructorInjection =  $this->constructorInjection($class, $arguments, $setterMethods);
        $node[] = new Expr\Assign(new Expr\Variable('is_singleton'), $this->normalizer->normalizeValue($isSingleton));
        $node[] = new Expr\Assign($instance, $constructorInjection);
        $setters = $this->setterInjection($instance, $setterMethods);
        foreach ($setters as $setter) {
            $node[] = $setter;
        }
        if ($postConstruct) {
            $node[] = $this->postConstruct($instance, $postConstruct);
        }

        return $node;
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

    private function setterInjection(Expr\Variable $instance, array $setterMethods)
    {
        $setters = [];
        foreach ($setterMethods as $setterMethod) {
            $method = $this->getPrivateProperty($setterMethod, 'method');
            $argumentsObject = $this->getPrivateProperty($setterMethod, 'arguments');
            $argumentsArray = $this->getPrivateProperty($argumentsObject, 'arguments');
            $args = [];
            foreach ($argumentsArray as $argument) {
                $args[] = $this->getArgStmt($argument);
            }

            $setters[] = new Expr\MethodCall($instance, $method, $args);
        }

        return $setters;
    }

    /**
     * @param Expr\Variable $instance
     * @param string        $postConstruct
     */
    private function postConstruct(Expr\Variable $instance, $postConstruct)
    {
        return new Expr\MethodCall($instance, $postConstruct);
    }

    /**
     * Add aop factory code if bindings are given
     *
     * @param Dependency $dependency
     * @param array      $node
     */
    private function getAopCode(Dependency $dependency, array &$node)
    {
        $newInstance = $this->getPrivateProperty($dependency, 'newInstance');
        $bind = $this->getPrivateProperty($newInstance, 'bind');
        $bind = $this->getPrivateProperty($bind, 'bind');
        $bindings = $this->getPrivateProperty($bind, 'bindings', null);
        if (is_null($bindings)) {
            return;
        }
        $methodBinding = [];
        foreach ($bindings as $method => $interceptors) {
            $items = [];
            foreach ($interceptors as $interceptor) {
                // $singleton('FakeAopInterface-*');
                $dependencyIndex = "{$interceptor}-*";
                $singleton = new Expr\FuncCall(new Expr\Variable('singleton'), [new Node\Arg(new Scalar\String_($dependencyIndex))]);
                // [$singleton('FakeAopInterface-*'), $singleton('FakeAopInterface-*');]
                $items[] = new Expr\ArrayItem($singleton);
            }
            $arr = new Expr\Array_($items);
            $methodBinding[] = new Expr\ArrayItem($arr, new Scalar\String_($method));
        }
        $bindingsProp = new Expr\PropertyFetch(new Expr\Variable('instance'), 'bindings');
        $node[] = new Expr\Assign($bindingsProp, new Expr\Array_($methodBinding));
    }

    private function getArgStmt(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        if ($dependencyIndex === 'Ray\Di\InjectionPointInterface-*') {
            return $this->getInjectionPoint();
        }
        $hasDependency = isset($this->container->getContainer()[$dependencyIndex]);
        if (! $hasDependency && $argument->isDefaultAvailable()) {
            $default = $argument->getDefaultValue();
            $node = $this->normalizer->normalizeValue($default);

            return $node;
        }
        $dependency = $this->container->getContainer()[$dependencyIndex];
        if ($dependency instanceof Instance) {
            return $this->normalizer->normalizeValue($dependency->value);
        }
        $isSingleton = $this->getPrivateProperty($dependency, 'isSingleton');
        $func = $isSingleton ? 'singleton' : 'prototype';
        $args = $this->getInjectionFuncParams($argument);

        $node = new Expr\FuncCall(new Expr\Variable($func), $args);

        return $node;
    }

    private function getInjectionPoint()
    {
        return new Expr\FuncCall(new Expr\Variable('injection_point'));
    }

    private function getInjectionFuncParams(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        $isProviderDependency = $this->container->getContainer()[$dependencyIndex] instanceof DependencyProvider;
        if ($isProviderDependency) {
            return $this->getInjectionProviderParams($argument);
        }

        return [new Node\Arg(new Scalar\String_((string) $argument))];
    }

    private function getInjectionProviderParams(Argument $argument)
    {
        $param = $argument->get();

        return [
            new Node\Arg(new Scalar\String_((string) $argument)),
            new Expr\Array_([
                new Node\Arg(new Scalar\String_($param->getDeclaringClass()->getName())),
                new Node\Arg(new Scalar\String_($param->getDeclaringFunction()->getName())),
                new Node\Arg(new Scalar\String_($param->getName()))
            ])
        ];
    }

    private function getPrivateProperty($object, $prop, $default = null)
    {
        try {
            $refProp = (new \ReflectionProperty($object, $prop));
        } catch (\Exception $e) {
            return $default;
        }
        $refProp->setAccessible(true);
        $value = $refProp->getValue($object);

        return $value;
    }
}

<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Ray\Aop\Interceptor;
use Ray\Compiler\Exception\NotCompiled;
use Ray\Di\Argument;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;
use Ray\Di\Exception\Unbound;
use Ray\Di\Instance;
use Ray\Di\Name;
use Ray\Di\SetterMethod;

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
     * @var ScriptInjector
     */
    private $injector;

    /**
     * @var Normalizer
     */
    private $normalizer;

    public function __construct(Container $container, ScriptInjector $injector = null)
    {
        $this->factory = new \PhpParser\BuilderFactory;
        $this->container = $container;
        $this->normalizer = new Normalizer;
        $this->injector = $injector;
    }

    /**
     * Return compiled dependency code
     *
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

    /**
     * Compile DependencyInstance
     *
     * @param Instance $instance
     *
     * @return Code
     */
    private function compileInstance(Instance $instance)
    {
        $node = $this->normalizer->normalizeValue($instance->value);

        return new Code(new Node\Stmt\Return_($node), false);
    }

    /**
     * Compile generic object dependency
     *
     * @param Dependency $dependency
     *
     * @return Code
     */
    private function compileDependency(Dependency $dependency)
    {
        $node = $this->getFactoryNode($dependency);
        $this->getAopCode($dependency, $node);
        $node[] = new Node\Stmt\Return_(new Node\Expr\Variable('instance'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $isSingleton = $this->getPrivateProperty($dependency, 'isSingleton');

        return new Code($node, $isSingleton);
    }

    /**
     * Compile dependency provider
     *
     * @param DependencyProvider $provider
     *
     * @return Code
     */
    private function compileDependencyProvider(DependencyProvider $provider)
    {
        $dependency = $this->getPrivateProperty($provider, 'dependency');
        $node = $this->getFactoryNode($dependency);
        $node[] = new Stmt\Return_(new Expr\MethodCall(new Expr\Variable('instance'), 'get'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $isSingleton = $this->getPrivateProperty($provider, 'isSingleton');

        return new Code($node, $isSingleton);
    }

    /**
     * Return generic factory code
     *
     * This code is used by Dependency and DependencyProvider
     *
     * @param DependencyInterface $dependency
     *
     * @return \PhpParser\Node[]
     */
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
    private function getFactoryCode($class, array $arguments, array $setterMethods, $postConstruct)
    {
        $node = [];
        $instance = new Expr\Variable('instance');
        // constructor injection
        $constructorInjection =  $this->constructorInjection($class, $arguments);
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

    /**
     * @param Expr\Variable  $instance
     * @param SetterMethod[] $setterMethods
     *
     * @return Expr\MethodCall[]
     */
    private function setterInjection(Expr\Variable $instance, array $setterMethods)
    {
        $setters = [];
        foreach ($setterMethods as $setterMethod) {
            $isOptional = $this->getPrivateProperty($setterMethod, 'isOptional');
            $method = $this->getPrivateProperty($setterMethod, 'method');
            $argumentsObject = $this->getPrivateProperty($setterMethod, 'arguments');
            $arguments = $this->getPrivateProperty($argumentsObject, 'arguments');
            $args = $this->getSetterParams($arguments, $isOptional);
            if (! $args) {
                continue;
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
        /** @var array $bindings */
        $bindings = $this->getPrivateProperty($bind, 'bindings', null);
        if (! $bindings || ! is_array($bindings)) {
            return;
        }
        $methodBinding = $this->getMethodBinding($bindings);
        $bindingsProp = new Expr\PropertyFetch(new Expr\Variable('instance'), 'bindings');
        $node[] = new Expr\Assign($bindingsProp, new Expr\Array_($methodBinding));
    }

    /**
     * Return method argument code
     *
     * @param Argument $argument
     *
     * @return Expr|Expr\FuncCall
     */
    private function getArgStmt(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        if ($dependencyIndex === 'Ray\Di\InjectionPointInterface-' . Name::ANY) {
            return $this->getInjectionPoint();
        }
        $hasDependency = isset($this->container->getContainer()[$dependencyIndex]);
        if (! $hasDependency) {
            return $this->getOnDemandDependency($argument);
        }
        $dependency = $this->container->getContainer()[$dependencyIndex];
        if ($dependency instanceof Instance) {
            return $this->normalizer->normalizeValue($dependency->value);
        }

        return $this->getPullDependency($argument, $dependency);
    }

    /**
     * Return on-demand dependency pull code for not compiled
     *
     * @param Argument $argument
     *
     * @return Expr|Expr\FuncCall
     */
    private function getOnDemandDependency(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        if (! $this->injector instanceof ScriptInjector) {
            return $this->getDefault($argument);
        }
        try {
            $isSingleton = $this->injector->isSingleton($dependencyIndex);
        } catch (NotCompiled $e) {
            return $this->getDefault($argument);
        }
        $func = $isSingleton ? 'singleton' : 'prototype';
        $args = $this->getInjectionProviderParams($argument);
        $node = new Expr\FuncCall(new Expr\Variable($func), $args);

        return $node;
    }

    /**
     * Return default argument value
     *
     * @param Argument $argument
     *
     * @return Expr
     */
    private function getDefault(Argument $argument)
    {
        if ($argument->isDefaultAvailable()) {
            $default = $argument->getDefaultValue();
            $node = $this->normalizer->normalizeValue($default);

            return $node;
        }

        throw new Unbound((string) $argument);
    }

    /**
     * Return arguments code for "$singleton" and "$prototype"
     *
     * @param Argument            $argument
     * @param DependencyInterface $dependency
     *
     * @return Expr\FuncCall
     */
    private function getPullDependency(Argument $argument, DependencyInterface $dependency)
    {
        $isSingleton = $this->getPrivateProperty($dependency, 'isSingleton');
        $func = $isSingleton ? 'singleton' : 'prototype';
        $args = $this->getInjectionFuncParams($argument);

        $node = new Expr\FuncCall(new Expr\Variable($func), $args);

        return $node;
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

    /**
     * Return dependency index argument
     *
     * [class, method, param] is added if dependency is provider for DI context
     *
     * @param Argument $argument
     *
     * @return array
     */
    private function getInjectionFuncParams(Argument $argument)
    {
        $dependencyIndex = (string) $argument;
        if ($this->container->getContainer()[$dependencyIndex] instanceof DependencyProvider) {
            return $this->getInjectionProviderParams($argument);
        }

        return [new Node\Arg(new Scalar\String_((string) $argument))];
    }

    /**
     * Return code for provider
     *
     * "$provider" needs [class, method, parameter] for InjectionPoint (Contextual Dependency Injection)
     *
     * @param Argument $argument
     *
     * @return array
     */
    private function getInjectionProviderParams(Argument $argument)
    {
        $param = $argument->get();

        return [
            new Node\Arg(new Scalar\String_((string) $argument)),
            new Expr\Array_([
                new Node\Arg(new Scalar\String_($param->getDeclaringClass()->name)),
                new Node\Arg(new Scalar\String_($param->getDeclaringFunction()->name)),
                new Node\Arg(new Scalar\String_($param->name))
            ])
        ];
    }

    /**
     * @param object $object
     * @param string $prop
     * @param mixed  $default
     *
     * @return mixed|null
     */
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

    /**
     * Return setter method parameters
     *
     * Return false when no dependency given and @ Inject(optional=true) annotated to setter method.
     *
     * @param Argument[] $arguments
     * @param bool       $isOptional
     *
     * @return Node\Arg[]
     */
    private function getSetterParams($arguments, $isOptional)
    {
        $args = [];
        foreach ($arguments as $argument) {
            try {
                $args[] = $this->getArgStmt($argument);
            } catch (Unbound $e) {
                if ($isOptional) {
                    return false;
                }
            }
        }

        return $args;
    }

    /**
     * @param Interceptor[] $bindings
     *
     * @return Expr\ArrayItem[]
     */
    private function getMethodBinding($bindings)
    {
        $methodBinding = [];
        foreach ($bindings as $method => $interceptors) {
            $items = [];
            foreach ($interceptors as $interceptor) {
                // $singleton('FakeAopInterface-*');
                $dependencyIndex = "{$interceptor}-" . Name::ANY;
                $singleton = new Expr\FuncCall(new Expr\Variable('singleton'), [new Node\Arg(new Scalar\String_($dependencyIndex))]);
                // [$singleton('FakeAopInterface-*'), $singleton('FakeAopInterface-*');]
                $items[] = new Expr\ArrayItem($singleton);
            }
            $arr = new Expr\Array_($items);
            $methodBinding[] = new Expr\ArrayItem($arr, new Scalar\String_($method));
        }

        return $methodBinding;
    }
}

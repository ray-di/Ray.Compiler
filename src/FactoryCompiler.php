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
use Ray\Compiler\Exception\NotCompiled;
use Ray\Di\Argument;
use Ray\Di\Container;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Instance;
use Ray\Di\Name;
use Ray\Di\SetterMethod;

class FactoryCompiler
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

    public function __construct(
        Container $container,
        Normalizer $normalizer,
        DependencyCompiler $compiler,
        InjectorInterface $injector = null
    ) {
        $this->container = $container;
        $this->normalizer = $normalizer;
        $this->compiler = $compiler;
        $this->injector = $injector;
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

        return $this->compiler->getPullDependency($argument, $dependency);
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
     * @param Expr\Variable $instance
     * @param string        $postConstruct
     */
    private function postConstruct(Expr\Variable $instance, $postConstruct)
    {
        return new Expr\MethodCall($instance, $postConstruct);
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
}

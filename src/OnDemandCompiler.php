<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use Ray\Compiler\Exception\NotCompiled;
use Ray\Di\Argument;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\SetterMethod;

final class OnDemandCompiler
{
    /**
     * @var InjectorInterface
     */
    private $injector;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var FactoryCompiler
     */
    private $factoryCompiler;

    /**
     * @var PrivateProperty
     */
    private $privateProperty;

    public function __construct(
        Normalizer $normalizer,
        FactoryCompiler $factoryCompiler,
        InjectorInterface $injector = null
    ) {
        $this->injector = $injector;
        $this->normalizer = $normalizer;
        $this->factoryCompiler = $factoryCompiler;
        $this->privateProperty = new PrivateProperty;
    }

    /**
     * Return on-demand dependency pull code for not compiled
     *
     * @return Expr|Expr\FuncCall
     */
    public function getOnDemandDependency(Argument $argument) : Expr
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
     * @param Expr\Variable  $instance
     * @param SetterMethod[] $setterMethods
     *
     * @return Expr\MethodCall[]
     */
    public function setterInjection(Expr\Variable $instance, array $setterMethods) : array
    {
        $setters = [];
        foreach ($setterMethods as $setterMethod) {
            $isOptional = $this->privateProperty->__invoke($setterMethod, 'isOptional');
            $method = $this->privateProperty->__invoke($setterMethod, 'method');
            $argumentsObject = $this->privateProperty->__invoke($setterMethod, 'arguments');
            $arguments = $this->privateProperty->__invoke($argumentsObject, 'arguments');
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
     *
     * @return Expr\MethodCall
     */
    public function postConstruct(Expr\Variable $instance, $postConstruct) : Expr\MethodCall
    {
        return new Expr\MethodCall($instance, $postConstruct);
    }

    /**
     * Return default argument value
     */
    private function getDefault(Argument $argument) : Expr
    {
        if ($argument->isDefaultAvailable()) {
            $default = $argument->getDefaultValue();

            return $this->normalizer->__invoke($default);
        }
        throw new Unbound($argument->getMeta());
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
    private function getSetterParams(array $arguments, bool $isOptional)
    {
        $args = [];
        foreach ($arguments as $argument) {
            try {
                $args[] = $this->factoryCompiler->getArgStmt($argument);
            } catch (Unbound $e) {
                if ($isOptional) {
                    return false;
                }
            }
        }

        return $args;
    }
}

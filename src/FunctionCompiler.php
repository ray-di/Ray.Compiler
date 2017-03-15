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
use Ray\Di\Argument;
use Ray\Di\Container;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;

/**
 * This file is part of the *** package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
class FunctionCompiler
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var PrivateProperty
     */
    private $privateProperty;

    public function __construct(Container $container, PrivateProperty $privateProperty)
    {
        $this->container = $container;
        $this->privateProperty = $privateProperty;
    }

    /**
     * Return arguments code for "$singleton" and "$prototype"
     *
     * @param Argument            $argument
     * @param DependencyInterface $dependency
     *
     * @return Expr\FuncCall
     */
    public function __invoke(Argument $argument, DependencyInterface $dependency)
    {
        $prop = $this->privateProperty;
        $isSingleton = $prop($dependency, 'isSingleton');
        $func = $isSingleton ? 'singleton' : 'prototype';
        $args = $this->getInjectionFuncParams($argument);

        $node = new Expr\FuncCall(new Expr\Variable($func), $args);

        return $node;
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
}

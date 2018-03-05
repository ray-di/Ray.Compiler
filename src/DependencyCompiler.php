<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;
use Ray\Di\Instance;
use Ray\Di\SetContextInterface;

final class DependencyCompiler implements SetContextInterface
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
     * @var ScriptInjector|null
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

    /**
     * @var IpQualifier|null
     */
    private $qualifier;

    private $context;

    /**
     * @var AopCode
     */
    private $aopCode;

    public function __construct(Container $container, ScriptInjector $injector = null)
    {
        $this->factory = new BuilderFactory;
        $this->container = $container;
        $this->normalizer = new Normalizer;
        $this->injector = $injector;
        $this->factoryCompiler = new FactoryCompiler($container, new Normalizer, $this, $injector);
        $this->privateProperty = new PrivateProperty;
        $this->aopCode = new AopCode($this->privateProperty);
    }

    /**
     * Return compiled dependency code
     */
    public function compile(DependencyInterface $dependency) : Code
    {
        if ($dependency instanceof Dependency) {
            return $this->compileDependency($dependency);
        } elseif ($dependency instanceof Instance) {
            return $this->compileInstance($dependency);
        } elseif ($dependency instanceof DependencyProvider) {
            return $this->compileDependencyProvider($dependency);
        }

        throw new \DomainException(\get_class($dependency));
    }

    /**
     * {@inheritdoc}
     */
    public function setContext($context) : void
    {
        $this->context = $context;
    }

    public function setQaulifier(IpQualifier $qualifer) : void
    {
        $this->qualifier = $qualifer;
    }

    /**
     * Return "return [$node, $isSingleton]" node
     */
    public function getReturnCode(Expr $instance, bool $isSingleton) : Node\Stmt\Return_
    {
        $bool = $isSingleton ? 'true' : 'false';
        $singletonInt = new Expr\ConstFetch(new Node\Name([$bool]));
        $return = new Node\Stmt\Return_(
            new Node\Expr\Array_(
                [
                new Expr\ArrayItem(
                    $instance
                ),
                new Expr\ArrayItem(
                    $singletonInt
                )
                ]
            )
        );

        return $return;
    }

    /**
     * Compile DependencyInstance
     */
    private function compileInstance(Instance $instance) : Code
    {
        $node = $this->normalizer->__invoke($instance->value);

        return new Code($this->getReturnCode($node, false));
    }

    /**
     * Compile generic object dependency
     */
    private function compileDependency(Dependency $dependency) : Code
    {
        $prop = $this->privateProperty;
        $node = $this->getFactoryNode($dependency);
        $this->aopCode->__invoke($dependency, $node);
        $isSingleton = $prop($dependency, 'isSingleton');
        $node[] = $this->getReturnCode(new Node\Expr\Variable('instance'), $isSingleton);
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $qualifer = $this->qualifier;
        $this->qualifier = null;

        return new Code($node, $isSingleton, $qualifer);
    }

    /**
     * Compile dependency provider
     */
    private function compileDependencyProvider(DependencyProvider $provider) : Code
    {
        $prop = $this->privateProperty;
        $dependency = $prop($provider, 'dependency');
        $node = $this->getFactoryNode($dependency);
        $provider->setContext($this);
        if ($this->context) {
            $node[] = $this->getSetContextCode($this->context); // $instance->setContext($this->context);
        }
        $isSingleton = $prop($provider, 'isSingleton');
        $methodCall = new MethodCall(new Expr\Variable('instance'), 'get');
        $node[] = $this->getReturnCode($methodCall, $isSingleton);
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $qualifer = $this->qualifier;
        $this->qualifier = null;

        return new Code($node, $isSingleton, $qualifer);
    }

    private function getSetContextCode(string $context) : MethodCall
    {
        $arg = new Node\Arg(new Node\Scalar\String_($context));

        return new MethodCall(new Expr\Variable('instance'), 'setContext', [$arg]);
    }

    /**
     * Return generic factory code
     *
     * This code is used by Dependency and DependencyProvider
     *
     * @return \PhpParser\Node[]
     */
    private function getFactoryNode(DependencyInterface $dependency) : array
    {
        $prop = $this->privateProperty;
        $newInstance = $prop($dependency, 'newInstance');
        // class name
        $class = $prop($newInstance, 'class');
        $setterMethods = (array) $prop($prop($newInstance, 'setterMethods'), 'setterMethods');
        $arguments = (array) $prop($prop($newInstance, 'arguments'), 'arguments');
        $postConstruct = (string) $prop($dependency, 'postConstruct');

        return $this->factoryCompiler->getFactoryCode($class, $arguments, $setterMethods, $postConstruct);
    }
}

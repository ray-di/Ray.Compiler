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
use PhpParser\Node\Stmt;
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
     * @var ScriptInjector
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
     * {@inheritdoc}
     */
    public function setContext($context)
    {
        $this->context = $context;
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
        $node = $this->normalizer->__invoke($instance->value);

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
        $prop = $this->privateProperty;
        $node = $this->getFactoryNode($dependency);
        $this->aopCode->__invoke($dependency, $node);
        $node[] = new Node\Stmt\Return_(new Node\Expr\Variable('instance'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $isSingleton = $prop($dependency, 'isSingleton');

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
        $prop = $this->privateProperty;
        $dependency = $prop($provider, 'dependency');
        $node = $this->getFactoryNode($dependency);
        $provider->setContext($this);
        if ($this->context) {
            $node[] = $this->getSetContextCode($this->context); // $instance->setContext($this->context);
        }
        $node[] = new Stmt\Return_(new Expr\MethodCall(new Expr\Variable('instance'), 'get'));
        $node = $this->factory->namespace('Ray\Di\Compiler')->addStmts($node)->getNode();
        $isSingleton = $prop($provider, 'isSingleton');

        return new Code($node, $isSingleton);
    }

    /**
     * @param string $context
     *
     * @return Expr\MethodCall
     */
    private function getSetContextCode($context)
    {
        $arg = new Node\Arg(new Node\Scalar\String_($context));
        $node = new Expr\MethodCall(new Expr\Variable('instance'), 'setContext', [$arg]);

        return $node;
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
        $prop = $this->privateProperty;
        $newInstance = $prop($dependency, 'newInstance');
        // class name
        $class = $prop($newInstance, 'class');
        $setterMethods = (array) $prop($prop($newInstance, 'setterMethods'), 'setterMethods');
        $arguments = (array) $prop($prop($newInstance, 'arguments'), 'arguments');
        $postConstruct = $prop($dependency, 'postConstruct');

        return $this->factoryCompiler->getFactoryCode($class, $arguments, $setterMethods, $postConstruct);
    }
}

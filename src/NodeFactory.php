<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use Ray\Di\Argument;
use Ray\Di\Arguments;
use Ray\Di\Exception\Unbound;
use Ray\Di\SetterMethod;

use function assert;
use function is_bool;
use function is_string;

final class NodeFactory
{
    /** @var Normalizer */
    private $normalizer;

    /** @var FactoryCode */
    private $factoryCompiler;

    /** @var PrivateProperty */
    private $privateProperty;

    public function __construct(
        Normalizer $normalizer,
        FactoryCode $factoryCompiler
    ) {
        $this->normalizer = $normalizer;
        $this->factoryCompiler = $factoryCompiler;
        $this->privateProperty = new PrivateProperty();
    }

    /**
     * @param SetterMethod[] $setterMethods
     *
     * @return Expr\MethodCall[]
     */
    public function getSetterInjection(Expr\Variable $instance, array $setterMethods): array
    {
        $setters = [];
        foreach ($setterMethods as $setterMethod) {
            $isOptional = ($this->privateProperty)($setterMethod, 'isOptional');
            assert(is_bool($isOptional));
            $method = ($this->privateProperty)($setterMethod, 'method');
            assert(is_string($method));
            $argumentsObject = ($this->privateProperty)($setterMethod, 'arguments');
            assert($argumentsObject instanceof Arguments);
            /** @var array<Argument> $arguments */
            $arguments = ($this->privateProperty)($argumentsObject, 'arguments');
            /** @var array<Node\Arg> $args */
            $args = $this->getSetterParams($arguments, $isOptional);
            if (! $args) {
                continue;
            }

            $setters[] = new Expr\MethodCall($instance, $method, $args);
        }

        return $setters;
    }

    public function getPostConstruct(Expr\Variable $instance, string $postConstruct): Expr\MethodCall
    {
        return new Expr\MethodCall($instance, $postConstruct);
    }

    /**
     * Return default argument value
     */
    public function getDefault(Argument $argument): Expr
    {
        if ($argument->isDefaultAvailable()) {
            $default = $argument->getDefaultValue();

            return ($this->normalizer)($default);
        }

        throw new Unbound($argument->getMeta());
    }

    /**
     * Return setter method parameters
     *
     * Return false when no dependency given and @ Inject(optional=true) annotated to setter method.
     *
     * @param Argument[] $arguments
     *
     * @return false|Node\Expr[]
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

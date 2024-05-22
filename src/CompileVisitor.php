<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Bind;
use Ray\Di\Arguments;
use Ray\Di\AspectBind;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\NewInstance;
use Ray\Di\SetterMethods;
use Ray\Di\VisitorInterface;
use ReflectionParameter;
use RuntimeException;

use function is_array;
use function is_object;
use function is_scalar;
use function serialize;
use function sprintf;
use function str_replace;
use function var_export;

final class CompileVisitor implements VisitorInterface
{
    /** @var InstanceScript */
    private $script;

    public function __construct(Container $container)
    {
        $this->script = new InstanceScript($container);
    }

    /** @inheritDoc */
    public function visitDependency(
        NewInstance $newInstance,
        ?string $postConstruct,
        bool $isSingleton
    ): string {
        $newInstance->accept($this);

        return $this->script->getScript($postConstruct, $isSingleton);
    }

    /** @inheritDoc */
    public function visitProvider(
        Dependency $dependency,
        string $context,
        bool $isSingleton
    ): string {
        $this->script->pushProviderContext($context, $isSingleton);
        $script = $dependency->accept($this);

        return str_replace('return $instance', 'return $instance->get()', (string) $script);
    }

    /** @inheritDoc */
    public function visitInstance($value): string
    {
        if (is_scalar($value) || is_array($value)) {
            return sprintf('return %s;', var_export($value, true));
        }

        if ($value === null) {
            return 'return null;';
        }

        if (is_object($value)) {
            return sprintf('return unserialize(\'%s\');', serialize($value));
        }

        throw new RuntimeException('Invalid instance value');
    }

    /** @inheritDoc */
    public function visitAspectBind(Bind $aopBind)
    {
        $this->script->pushAspectBind($aopBind);
    }

    /** @inheritDoc */
    public function visitNewInstance(
        string $class,
        SetterMethods $setterMethods,
        ?Arguments $arguments,
        ?AspectBind $bind
    ) {
        $setterMethods->accept($this);
        if ($arguments) {
            $arguments->accept($this);
        }

        if ($bind) {
            $bind->accept($this);
        }

        $this->script->pushClass($class);
    }

    /** @inheritDoc */
    public function visitSetterMethods(
        array $setterMethods
    ) {
        foreach ($setterMethods as $setterMethod) {
            $setterMethod->accept($this);
        }
    }

    /** @inheritDoc */
    public function visitSetterMethod(string $method, Arguments $arguments)
    {
        $arguments->accept($this);
        $this->script->pushMethod($method);
    }

    /** @inheritDoc */
    public function visitArguments(
        array $arguments
    ) {
        foreach ($arguments as $argument) {
            $argument->accept($this);
        }
    }

    /** @inheritDoc */
    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter
    ): void {
        $this->script->addArg($index, $isDefaultAvailable, $defaultValue, $parameter);
    }
}

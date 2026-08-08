<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Override;
use Ray\Aop\Bind;
use Ray\Di\Arguments;
use Ray\Di\AspectBind;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\NewInstance;
use Ray\Di\SetterMethods;
use Ray\Di\VisitorInterface;
use ReflectionParameter;

use function assert;
use function gettype;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function serialize;
use function sprintf;
use function strrpos;
use function substr;
use function var_export;

use const PHP_EOL;

final class CompileVisitor implements VisitorInterface
{
    private readonly InstanceScript $script;

    public function __construct(Container $container)
    {
        $this->script = new InstanceScript($container);
    }

    public function consumeInjectionPointUsage(): bool
    {
        return $this->script->consumeInjectionPointUsage();
    }

    /** @inheritDoc */
    #[Override]
    public function visitDependency(
        NewInstance $newInstance,
        string|null $postConstruct,
        bool $isSingleton,
    ): string {
        $newInstance->accept($this);

        return $this->script->getScript($postConstruct, $isSingleton);
    }

    /** @inheritDoc */
    #[Override]
    public function visitProvider(
        Dependency $dependency,
        string $context,
        bool $isSingleton,
    ): string {
        $this->script->pushProviderContext($context);
        $script = $dependency->accept($this);
        assert(is_string($script));
        $pos = strrpos($script, InstanceScript::RETURN_INSTANCE);
        assert($pos !== false);

        return substr($script, 0, $pos) . $this->getProviderScript($isSingleton);
    }

    /** @inheritDoc */
    #[Override]
    public function visitInstance($value): string
    {
        if ($value === null || is_scalar($value)) {
            return sprintf('return %s;', var_export($value, true));
        }

        assert(is_object($value) || is_array($value), 'Invalid instance type:' . gettype($value));

        return sprintf('return unserialize(%s);', var_export(serialize($value), true));
    }

    /** @inheritDoc */
    #[Override]
    public function visitAspectBind(Bind $aopBind): void
    {
        $this->script->pushAspectBind($aopBind);
    }

    /** @inheritDoc */
    #[Override]
    public function visitNewInstance(
        string $class,
        SetterMethods $setterMethods,
        Arguments|null $arguments,
        AspectBind|null $bind,
    ): void {
        $setterMethods->accept($this);
        if ($arguments instanceof Arguments) {
            $arguments->accept($this);
        }

        if ($bind instanceof AspectBind) {
            $bind->accept($this);
        }

        $this->script->pushClass($class);
    }

    /** @inheritDoc */
    #[Override]
    public function visitSetterMethods(
        array $setterMethods,
    ): void {
        foreach ($setterMethods as $setterMethod) {
            $setterMethod->accept($this);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function visitSetterMethod(string $method, Arguments $arguments): void
    {
        $arguments->accept($this);
        $this->script->pushMethod($method);
    }

    /** @inheritDoc */
    #[Override]
    public function visitArguments(array $arguments): void
    {
        foreach ($arguments as $argument) {
            $argument->accept($this);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter,
    ): void {
        $this->script->addArg($index, $isDefaultAvailable, $defaultValue, $parameter);
    }

    private function getProviderScript(bool $isSingleton): string
    {
        $lines = ['$instance = $instance->get();'];
        if ($isSingleton) {
            $lines[] = '$singletons[$dependencyIndex] = $instance;';
        }

        $lines[] = InstanceScript::RETURN_INSTANCE;

        return implode(PHP_EOL, $lines);
    }
}

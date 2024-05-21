<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Bind;
use Ray\Di\Argument;
use Ray\Di\Arguments;
use Ray\Di\AspectBind;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\Exception\Unbound;
use Ray\Di\NewInstance;
use Ray\Di\SetterMethod;
use Ray\Di\SetterMethods;
use Ray\Di\VisitorInterface;
use ReflectionParameter;
use RuntimeException;

use function is_array;
use function is_null;
use function is_object;
use function is_scalar;
use function serialize;
use function sprintf;
use function str_replace;
use function var_export;

use const PHP_EOL;

final class CompileVisitor implements VisitorInterface
{
    /** @var Container */
    private $container;

    /** @var InstanceScript */
    private $script;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->script = new InstanceScript();
    }

    public function visitAspectBind(Bind $aopBind)
    {
        $this->script->pushAspectBind($aopBind);
    }

    public function visitProvider(
        Dependency $dependency,
        string $context
    ): string {
        $script = $dependency->accept($this);
        $providerScript = $context ? sprintf("\$instance->setContext('%s')", $context) : '';
        $providerScript .= PHP_EOL . 'return $instance->get()';

        return str_replace('return $instance', $providerScript, $script);
    }

    public function visitInstance($value): string
    {
        if (is_scalar($value) || is_array($value)) {
            return sprintf('return %s;', var_export($value, true));
        }

        if (is_null($value)) {
            return 'return null;';
        }

        if (is_object($value)) {
            return sprintf('return unserialize(\'%s\');', serialize($value));
        }

        throw new RuntimeException('Invalid instance value');
    }

    public function visitDependency(
        NewInstance $newInstance,
        ?string $postConstruct,
        bool $isSingleton
    ): string {
        $newInstance->accept($this);

        return $this->script->getScript($postConstruct, $isSingleton);
    }

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

    /**
     * @param SetterMethod[] $setterMethods
     */
    public function visitSetterMethods(
        array $setterMethods
    ) {
        foreach ($setterMethods as $setterMethod) {
            $setterMethod->accept($this);
        }
    }

    public function visitSetterMethod(string $method, Arguments $arguments)
    {
        $arguments->accept($this);
        $this->script->pushMethod($method);
    }

    /**
     * @param Argument[] $arguments
     */
    public function visitArguments(
        array $arguments
    ) {
        foreach ($arguments as $argument) {
            $argument->accept($this);
        }
    }

    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter
    ): void {
        try {
            if ($index === 'Ray\Di\InjectorInterface-') {
                $this->script->addInstanceArg('$injector()');

                return;
            }

            if ($index === 'Ray\Di\InjectionPointInterface-') {
                $this->script->addInstanceArg('$injectionPoint()');

                return;
            }

            if ($index === 'Ray\Di\MethodInvocationProvider-') {
                $this->script->addInstanceArg('$singleton(\'Ray\Di\MethodInvocationProvider-\')');

                return;
            }

            $this->script->addArgDependency($this->container->isSingleton($index), $index, $parameter);
        } catch (Unbound $e) {
            if ($index === 'Ray\Di\MultiBinding\MultiBindings-') {
                return;
            }

            if (! $isDefaultAvailable) {
                throw new Unbound($index);
            }

            $this->script->addInstanceArg(var_export($defaultValue, true));
        }
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Bind as AopBind;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\DependencyProvider;
use Ray\Di\Instance;
use Ray\Di\SetContextInterface;
use ReflectionParameter;

use function array_unshift;
use function assert;
use function implode;
use function is_a;
use function is_iterable;
use function is_object;
use function is_string;
use function serialize;
use function sprintf;
use function unserialize;
use function var_export;

use const PHP_EOL;

final class InstanceScript
{
    public const RAY_DI_INJECTOR_INTERFACE = 'Ray\Di\InjectorInterface-';
    public const RAY_DI_INJECTION_POINT_INTERFACE = 'Ray\Di\InjectionPointInterface-';

    /** @var array<mixed> */
    private $args = [];

    /** @var array<string> */
    private $formerLines = []; // Constructor injection and AOP

    /** @var array<string> */
    private $laterLines = [];  // Setter injection and postConstruct

    /** @var string */
    private $context = '';

    /** @var bool */
    private $implementsSetContext = false;

    /** @var bool|null */
    private $isSingleton = null;

    /** @var array<DependencyInterface> */
    private $container;

    public function __construct(Container $container)
    {
        $container->sort();
        $this->container = $container->getContainer();
    }

    /** @param mixed $defaultValue */
    public function addArg(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter): void
    {
        if (! isset($this->container[$index])) {
            if ($isDefaultAvailable) {
                $this->addInstanceArg($defaultValue);

                return;
            }

            if ($index === self::RAY_DI_INJECTOR_INTERFACE) {
                $this->args[] = '$injector()';

                return;
            }

            if ($index === self::RAY_DI_INJECTION_POINT_INTERFACE) {
                $this->args[] = '\Ray\Compiler\InjectionPoint::getInstance($ip)';

                return;
            }

            throw new Unbound($index);
        }

        $dependency = $this->container[$index];
        if ($dependency instanceof Dependency || $dependency instanceof DependencyProvider) {
            $this->addDependencyArg($dependency->isSingleton(), $index, $parameter);

            return;
        }

        assert($dependency instanceof Instance, 'Invalid instance value');
        $this->addInstanceArg($dependency->value);
    }

    private function addDependencyArg(bool $isSingleton, string $index, ReflectionParameter $parameter): void
    {
        /** @psalm-suppress PossiblyNullReference / The $parameter here can never be null */
        $ip = sprintf("['%s', '%s', '%s']", $parameter->getDeclaringClass()->getName(), $parameter->getDeclaringFunction()->getName(), $parameter->name); //@phpstan-ignore-line
        $func = $isSingleton ? '$singleton' : '$prototype';
        $arg = sprintf("%s('%s', %s)", $func, $index, $ip);
        $this->args[] = $arg;
    }

    /** @param mixed $default */
    public function addInstanceArg($default): void
    {
        if (is_object($default)) {
            $this->args[] = sprintf('unserialize(\'%s\')', serialize($default));

            return;
        }

        $this->args[] = var_export($default, true);
    }

    public function pushMethod(string $method): void
    {
        $this->laterLines[] = sprintf('$instance->%s(%s);', $method, implode(', ', $this->args));
        $this->args = [];
    }

    public function pushClass(string $class): void
    {
        $this->implementsSetContext = is_a($class, SetContextInterface::class, true);

        array_unshift($this->formerLines, sprintf('$instance = new \%s(%s);', $class, implode(', ', $this->args)));
        $this->args = [];
    }

    public function pushProviderContext(string $context, bool $isSingleton): void
    {
        $this->context = $context;
        $this->isSingleton = $isSingleton;
    }

    public function pushAspectBind(AopBind $aopBind): void
    {
        $aopBindings = unserialize((string) ($aopBind));
        assert(is_iterable($aopBindings));
        foreach ($aopBindings as &$bindings) {
            foreach ($bindings as &$binding) {
                $binding = sprintf('$singleton(\'%s-\')', $binding);
            }
        }

        $interceptors = [];
        foreach ($aopBindings as $method => $aopBinding) {
            $interceptors[] =  sprintf('\'%s\' => [%s]', $method, implode(', ', $aopBinding));
        }

        $this->formerLines[] = sprintf('$instance->bindings = [%s];', implode(', ', $interceptors));
    }

    public function getScript(?string $postConstruct, bool $isSingleton): string
    {
        if (is_string($postConstruct)) {
            $this->laterLines[] = sprintf('$instance->%s();', $postConstruct);
        }

        if ($this->implementsSetContext) {
            $this->laterLines[] = sprintf('$instance->setContext(%s);', var_export($this->context, true));
        }

        $isSingleton = $this->isSingleton ?? $isSingleton;
        $this->laterLines[] = sprintf('$isSingleton = %s;', $isSingleton ? 'true' : 'false');
        $this->laterLines[] = 'return $instance;';

        $script = implode(PHP_EOL, $this->formerLines) . PHP_EOL . implode(PHP_EOL, $this->laterLines);
        $this->formerLines = [];
        $this->laterLines = [];
        $this->isSingleton = null;

        return $script;
    }
}

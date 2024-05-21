<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\Bind as AopBind;
use ReflectionParameter;

use function array_unshift;
use function implode;
use function sprintf;
use function unserialize;

use const PHP_EOL;

final class InstanceScript
{
    private $args = [];
    private $aopBindings = '';
    private $lines = [];

    public function addArgDependency(bool $isSingleton, string $index, ReflectionParameter $parameter): void
    {
        if ($index === 'Ray\Di\InjectorInterface-') {
            $this->args[] = '$injector()';

            return;
        }

        $ip = sprintf("['%s', '%s', '%s']", $parameter->getDeclaringClass()->name, $parameter->getDeclaringFunction()->getName(), $parameter->name);
        $func = $isSingleton ? '$singleton' : '$prototype';
            $arg = sprintf("%s('%s', %s)", $func, $index, $ip);
        $this->args[] = $arg;
    }

    public function addInstanceArg(string $default): void
    {
        $this->args[] = $default;
    }

    public function pushMethod(string $method): void
    {
        $this->lines[] = sprintf('$instance->%s(%s);', $method, implode(', ', $this->args));
        $this->args = [];
    }

    public function pushClass(string $class): void
    {
        array_unshift($this->lines, sprintf('$instance = new \%s(%s);', $class, implode(', ', $this->args)));
        $this->args = [];
    }

    public function pushAspectBind(AopBind $aopBind): void
    {
        $aopBindings = unserialize((string) ($aopBind));
        foreach ($aopBindings as $method => &$bindings) {
            foreach ($bindings as &$binding) {
                $binding = sprintf('$singleton(\'%s-\')', $binding);
            }
        }

        $interceptors = [];
        foreach ($aopBindings as $method => $bindings) {
            $interceptors[] =  sprintf('\'%s\' => [%s]', $method, implode(', ', $bindings));
        }

        $this->aopBindings = sprintf('$instance->bindings = [%s];', implode(', ', $interceptors));
    }

    public function getScript(?string $postConstruct, bool $isSingleton): string
    {
        if ($postConstruct) {
            $this->lines[] = sprintf('$instance->%s();', $postConstruct);
        }

        if ($this->aopBindings) {
            $this->lines[] = $this->aopBindings;
            $this->aopBindings = '';
        }

        $this->lines[] = sprintf('$isSingleton = %s;', $isSingleton ? 'true' : 'false');
        $this->lines[] = 'return $instance;';

        $script = implode(PHP_EOL, $this->lines);
        $this->lines = [];

        return $script;
    }
}

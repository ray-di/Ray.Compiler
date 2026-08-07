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
use function is_array;
use function is_object;
use function is_string;
use function serialize;
use function sprintf;
use function var_export;

use const PHP_EOL;

final class InstanceScript
{
    public const RAY_DI_INJECTOR_INTERFACE = 'Ray\Di\InjectorInterface-';
    public const RAY_DI_INJECTION_POINT_INTERFACE = 'Ray\Di\InjectionPointInterface-';
    public const RAY_DI_SCRIPT_DIR = '-Ray\Di\Annotation\ScriptDir';
    public const COMMENT = '// prototype';

    /** @var array<mixed> */
    private array $args = [];

    /** @var array<string> */
    private array $formerLines = []; // Constructor injection and AOP

    /** @var array<string> */
    private array $laterLines = [];  // Setter injection and postConstruct
    private string $context = '';
    private bool $implementsSetContext = false;

    /** @var array<DependencyInterface> */
    private array $container;

    public function __construct(Container $container)
    {
        $container->sort();
        $this->container = $container->getContainer();
    }

    /** @param mixed $defaultValue */
    public function addArg(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter): void
    {
        // The script dir is where the generated scripts live: resolve it at
        // runtime, never bake the compile-time path.
        if ($index === self::RAY_DI_SCRIPT_DIR) {
            $this->args[] = '__DIR__';

            return;
        }

        if (! isset($this->container[$index])) {
            if ($isDefaultAvailable) {
                $this->addInstanceArg($defaultValue);

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
        // A backslash is legal in an index (class-string qualifiers), so the literal must be
        // escaped: emitted raw, a trailing backslash would escape the closing quote.
        $indexLiteral = var_export($index, true);
        $filePath = sprintf('/%s.php', ScriptName::forIndex($index));
        // Add prototype or singleton
        $this->args[] = $isSingleton ?
            sprintf("\Ray\Compiler\singleton(\$scriptDir, \$singletons, %s, '%s', %s)", $indexLiteral, $filePath, $ip) :
            sprintf("\Ray\Compiler\prototype(\$scriptDir, \$singletons, %s, '%s', %s)", $indexLiteral, $filePath, $ip);
    }

    /** @param mixed $default */
    public function addInstanceArg($default): void
    {
        if (is_object($default) || is_array($default)) {
            $this->args[] = sprintf('unserialize(%s)', var_export(serialize($default), true));

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

    public function pushProviderContext(string $context): void
    {
        $this->context = $context;
    }

    public function pushAspectBind(AopBind $aopBind): void
    {
        $aopBindings = $aopBind->getBindings();
        foreach ($aopBindings as &$bindings) {
            /** @var array<int, string> $bindings */
            foreach ($bindings as &$binding) {
                $index = $binding . '-';
                $filePath = sprintf('/%s.php', ScriptName::forIndex($index));
                $binding = sprintf("\\Ray\\Compiler\\singleton(\$scriptDir, \$singletons, %s, '%s')", var_export($index, true), $filePath);
            }
        }

        $interceptors = [];
        foreach ($aopBindings as $method => $aopBinding) {
            /** @var array<int, string> $aopBinding */
            $interceptors[] =  sprintf('\'%s\' => [%s]', $method, implode(', ', $aopBinding));
        }

        $this->formerLines[] = sprintf('$instance->bindings = [%s    %s%s];', PHP_EOL, implode(', ' . PHP_EOL . '    ', $interceptors), PHP_EOL);
    }

    public function getScript(string|null $postConstruct, bool $isSingleton): string
    {
        $initLines = $this->initializationLines($postConstruct);
        // A singleton must be resolvable from its own initialization, so it is cached before
        // initialization runs. Every step after that assignment shares one rollback: the cache
        // entry must never outlive a failed initialization.
        $isProvisional = $isSingleton && $initLines !== [];
        foreach ($isProvisional ? self::provisionalLines($initLines) : $initLines as $line) {
            $this->laterLines[] = $line;
        }

        $this->laterLines[] = self::COMMENT;
        if ($isSingleton && ! $isProvisional) {
            $this->laterLines[] = '$singletons[$dependencyIndex] = $instance;';
        }

        $this->laterLines[] = 'return $instance;';

        $script = implode(PHP_EOL, $this->formerLines) . PHP_EOL . implode(PHP_EOL, $this->laterLines);
        $this->formerLines = [];
        $this->laterLines = [];

        return $script;
    }

    /**
     * Lifecycle calls that run on the constructed instance
     *
     * @return list<string>
     */
    private function initializationLines(string|null $postConstruct): array
    {
        $lines = [];
        if (is_string($postConstruct)) {
            $lines[] = sprintf('$instance->%s();', $postConstruct);
        }

        if ($this->implementsSetContext) {
            $lines[] = sprintf('$instance->setContext(%s);', var_export($this->context, true));
        }

        return $lines;
    }

    /**
     * @param list<string> $initLines
     *
     * @return list<string>
     */
    private static function provisionalLines(array $initLines): array
    {
        $lines = ['$singletons[$dependencyIndex] = $instance;', 'try {'];
        foreach ($initLines as $line) {
            $lines[] = '    ' . $line;
        }

        $lines[] = '} catch (\Throwable $e) {';
        $lines[] = '    unset($singletons[$dependencyIndex]);';
        $lines[] = '    throw $e;';
        $lines[] = '}';

        return $lines;
    }
}

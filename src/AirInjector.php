<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\InjectionPointUnbound;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;
use ReflectionParameter;

use function file_exists;
use function in_array;
use function spl_autoload_register;
use function sprintf;
use function str_replace;

final class AirInjector implements InjectorInterface
{
    /** @var string */
    private $scriptDir;

    /**
     * Injection Point
     *
     * [$class, $method, $parameter]
     *
     * @var array{0: string, 1: string, 2: string}
     */
    private $ip = ['', '', ''];

    /**
     * Singleton instance container
     *
     * @var array<object>
     */
    private $singletons = [];

    /** @var array<string> */
    private static $scriptDirs = [];

    /**
     * @param string $scriptDir generated instance script folder path
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $this->registerLoader();
    }

    public function __wakeup()
    {
        $this->registerLoader();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @psalm-suppress UnresolvableInclude
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        static $prototype;
        static $singleton;
        static $injector;
        static $injectionPoint;

        if ($prototype === null) {
            $injectionPoint = function (): InjectionPoint {
                if ($this->ip[0] === '') {
                    throw new InjectionPointUnbound();
                }

                return new InjectionPoint(
                    new ReflectionParameter([$this->ip[0], $this->ip[1]], $this->ip[2])
                );
            };

            $injector = function (): self {
                return $this;
            };

            $prototype =
                /**
                 * @param array{0: string, 1: string, 2: string} $ip
                 *
                 * @return mixed
                 */
                function (string $dependencyIndex, array $ip = ['', '', '']) use ($injectionPoint, &$prototype, $injector, &$singleton) { // @phpstan-ignore-line
                    $this->ip = $ip; // @phpstan-ignore-line

                    return require $this->getInstanceFile($dependencyIndex);
                };
            $singleton =
                /**
                 * @param array{0: string, 1: string, 2: string} $ip
                 *
                 * @return mixed
                 */
                function (string $dependencyIndex, $ip) use ($injectionPoint, $prototype, $injector, &$singleton) { // @phpstan-ignore-line
                    if (isset($this->singletons[$dependencyIndex])) {
                        return $this->singletons[$dependencyIndex];
                    }

                    $this->ip = $ip;

                    $instance = require $this->getInstanceFile($dependencyIndex);
                    $this->singletons[$dependencyIndex] = $instance;

                    return $instance;
                };
            $scriptDir = $this->scriptDir;
        }

        $dependencyIndex = $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        /** @psalm-suppress UnresolvableInclude */
        $instance = require $this->getInstanceFile($dependencyIndex);
        /** @psalm-suppress UndefinedVariable */
        $isSingleton = isset($isSingleton) && $isSingleton;
        if ($isSingleton) {
            $this->singletons[$dependencyIndex] = $instance;
        }

        /**
         * @psalm-var T $instance
         * @phpstan-var mixed $instance
         */
        return $instance;
    }

    /**
     * Return compiled script file name
     */
    private function getInstanceFile(string $dependencyIndex): string
    {
        $file = sprintf('%s/%s.php', $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (file_exists($file)) {
            return $file;
        }

        throw new Unbound($dependencyIndex);
    }

    private function registerLoader(): void
    {
        if (in_array($this->scriptDir, self::$scriptDirs, true)) {
            return;
        }

        if (self::$scriptDirs === []) {
            spl_autoload_register(
                static function (string $class): void {
                    foreach (self::$scriptDirs as $scriptDir) {
                        $file = sprintf('%s/%s.php', $scriptDir, str_replace('\\', '_', $class));
                        if (file_exists($file)) {
                            require_once $file; // @codeCoverageIgnore
                        }
                    }
                }
            );
        }

        self::$scriptDirs[] = $this->scriptDir;
    }
}

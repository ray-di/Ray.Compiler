<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function assert;
use function file_exists;
use function in_array;
use function spl_autoload_register;
use function sprintf;
use function str_replace;

/**
 * @psalm-import-type ScriptDir from CompileInjector
 * @psalm-import-type Ip from CompileInjector
 * @psalm-import-type Singleton from CompileInjector
 * @psalm-import-type Prottype from CompileInjector
 * @psalm-import-type InjectionPoint from CompileInjector
 * @psalm-import-type Injector from CompileInjector
 * @psalm-import-type InstanceFunctions from CompileInjector
 * @psalm-type Injector = callable(): InjectorInterface
 */
final class AirInjector implements InjectorInterface
{
    /** @var ScriptDir */
    private $scriptDir;

    /**
     * Singleton instance container
     *
     * @var array<object>
     */
    private $singletons = [];

    /** @var array<ScriptDir> */
    private static $scriptDirs = [];

    /**
     * @param ScriptDir $scriptDir generated instance script folder path
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct(string $scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $this->registerLoader();
    }

    public function __wakeup()
    {
        $this->registerLoader();
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) // @phpstan-ignore-line
     * @psalm-suppress UnresolvableInclude
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        // Return singleton if exists
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        // Load functions
        /** @psalm-suppress UndefinedVariable */
        if (! isset($prototype)) { // @phpstan-ignore-line
            [$prototype, $singleton, $injector] = $this->getFunctions();
        }

        // Load instance with injection
        $scriptFile = $this->getInstanceFile($dependencyIndex);
        assert(file_exists($scriptFile)); // soothe Psalm
        /** @var mixed $instance */
        $instance = require $scriptFile;

        // Save singleton
        /** @psalm-suppress UndefinedVariable */
        if (isset($isSingleton) && $isSingleton) {
            /** @var object $instance */
            $this->singletons[$dependencyIndex] = $instance;
        }

        /** @pslam-var T $instance */
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

    /** @return InstanceFunctions */
    private function getFunctions(): array // @phpstan-ignore-line
    {
        /** @var Prottype $prototype */
        $prototype = // @phpstan-ignore-line
            /**
             * @param Ip $ip
             *
             * @return mixed
             */
            function (string $dependencyIndex, ?array $ip = null) {
                $instanceFile = $this->getInstanceFile($dependencyIndex);
                assert(file_exists($instanceFile)); // soothe Psalm

                return require $instanceFile;
            };

        /** @var Singleton $singleton */
        $singleton = // @phpstan-ignore-line
            /**
             * @param Ip $ip
             *
             * @return mixed
             */
            function (string $dependencyIndex, ?array $ip = null) {
                if (isset($this->singletons[$dependencyIndex])) {
                    return $this->singletons[$dependencyIndex];
                }

                $scriptFile =  $this->getInstanceFile($dependencyIndex);
                assert(file_exists($scriptFile)); // soothe Psalm
                /** @var object $instance */
                $instance = require $scriptFile;
                $this->singletons[$dependencyIndex] = $instance;

                return $instance;
            };
        /** @var InjectionPoint $injectionPoint */ // @phpstan-ignore-line

        /** @var Injector $injector */
        // @phpstan-ignore-next-line
        $injector = function (): self {
            return $this;
        };

        return [$prototype, $singleton, $injector];
    }
}

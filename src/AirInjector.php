<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function file_exists;
use function in_array;
use function is_dir;
use function is_readable;
use function realpath;
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
        if (! is_dir($scriptDir) || ! is_readable($scriptDir)) {
            throw new ScriptDirNotReadable($scriptDir);
        }

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
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        // Return singleton if exists
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        // Load instance with injection
        $scriptFile = sprintf('%s/%s.php', $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (! file_exists($scriptFile)) {
            throw new Unbound($dependencyIndex); // Binding not found
        }

        // Two variables in $scriptFile
        //
        // @var string       $scriptDir
        // @var array<mixed> $singletons
        /** @psalm-suppress  UnsupportedPropertyReferenceUsage */
        $singletons = &$this->singletons;
        $scriptDir = realpath($this->scriptDir);

        //　Injection
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

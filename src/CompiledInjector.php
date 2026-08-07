<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Override;
use Ray\Compiler\Exception\InvalidQualifier;
use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function file_exists;
use function in_array;
use function is_dir;
use function is_readable;
use function realpath;
use function spl_autoload_register;
use function sprintf;

/**
 * Compiled Injector
 *
 * An injector that requires all bindings to be pre-compiled into PHP code.
 * Use Ray\Compiler\Compiler to compile the bindings.
 * Runtime compilation is not supported.
 *
 * @psalm-import-type ScriptDir from Types
 * @psalm-import-type Singletons from Types
 * @psalm-import-type ScriptDirs from Types
 */
final class CompiledInjector implements ScriptInjectorInterface
{
    /** @var ScriptDir */
    private readonly string $scriptDir;

    /**
     * Singleton instance container
     *
     * @var Singletons
     */
    private array $singletons = [];

    /**
     * @psalm-import-type ScriptDirs from Types
     * @var ScriptDirs
     */
    private static $scriptDirs = [];

    /**
     * @param ScriptDir $scriptDir generated instance script folder path
     *
     * @psalm-suppress UnresolvableInclude
     * @ScriptDir
     */
    public function __construct(#[ScriptDir]
    string $scriptDir,)
    {
        $realPath = realpath($scriptDir);
        if ($realPath === false || ! is_dir($realPath) || ! is_readable($realPath)) {
            $message = sprintf('Script directory "%s" is not readable. See https://ray-di.github.io/Ray.Compiler/error/ScriptDirNotReadable', $scriptDir);

            throw new ScriptDirNotReadable($message);
        }

        /** @psalm-var ScriptDir $realPath */
        $this->scriptDir = $realPath;
        $this->cacheInjector();
        $this->registerLoader();
    }

    public function __wakeup()
    {
        $this->cacheInjector();
        $this->registerLoader();
    }

    /**
     * {@inheritDoc}
     *
     * @template T
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) // @phpstan-ignore-line
     */
    #[Override]
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        try {
            $scriptFile = sprintf('%s/%s.php', $this->scriptDir, ScriptName::forIndex($dependencyIndex));
        } catch (InvalidQualifier $e) {
            // An unsafe index can never have been compiled
            throw new Unbound($dependencyIndex, 0, $e);
        }

        if (! file_exists($scriptFile)) {
            throw new Unbound($dependencyIndex);
        }

        /** @psalm-suppress  UnsupportedPropertyReferenceUsage */
        $singletons = &$this->singletons;
        $scriptDir = $this->scriptDir; // already realpath()d in the constructor

        // $scriptDir, $singletons, and $dependencyIndex can be used in the included file
        /** @var mixed $instance */
        $instance = require $scriptFile;

        /** @psalm-var T $instance */
        return $instance;
    }

    private function cacheInjector(): void
    {
        $this->singletons[InjectorInterface::class . '-' . Name::ANY] = $this;
    }

    private function registerLoader(): void
    {
        $scriptDir = $this->scriptDir;
        if (in_array($scriptDir, self::$scriptDirs, true)) {
            return;
        }

        if (self::$scriptDirs === []) {
            spl_autoload_register(
                // @codeCoverageIgnoreStart
                static function (string $class): void {
                    foreach (self::$scriptDirs as $scriptDir) {
                        $file = sprintf('%s/%s.php', $scriptDir, ScriptName::forIndex($class));
                        if (file_exists($file)) {
                            require_once $file;
                        }
                    }
                },
                // @codeCoverageIgnoreEnd
            );
        }

        self::$scriptDirs[] = $scriptDir;
    }
}

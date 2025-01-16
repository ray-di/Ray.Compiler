<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\Name;

use function array_shift;
use function count;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_dir;
use function is_readable;
use function realpath;
use function rtrim;
use function spl_autoload_register;
use function sprintf;
use function str_repeat;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * Compiled Injector
 *
 * An injector that requires all bindings to be pre-compiled into PHP code.
 * Use Ray\Compiler\Compiler to compile the bindings.
 * Runtime compilation is not supported.
 *
 * @psalm-import-type ScriptDir from Types
 * @psalm-import-type Singletons from Types
 */
final class CompiledInjector implements ScriptInjectorInterface
{
    /**
     * Relative path to the generated instance script folder
     *
     * @var string
     */
    private $relativePath;

    /**
     * Singleton instance container
     *
     * @var Singletons
     */
    private $singletons = [];

    /** @var array<ScriptDir> */
    private static $scriptDirs = [];

    /**
     * @param ScriptDir $scriptDir generated instance script folder path
     *
     * @psalm-suppress UnresolvableInclude
     * @ScriptDir
     */
    #[ScriptDir]
    public function __construct(string $scriptDir)
    {
        $realPath = realpath($scriptDir);
        if ($realPath === false || ! is_dir($realPath) || ! is_readable($realPath)) {
            throw new ScriptDirNotReadable($scriptDir);
        }

        /** @psalm-var ScriptDir $realPath */
        $this->relativePath = $this->getRelativePath($realPath);
        $this->registerLoader();
    }

    public function __wakeup()
    {
        $this->registerLoader();
    }

    /**
     * {@inheritDoc}
     *
     * @template T
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) // @phpstan-ignore-line
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        $scriptDir = __DIR__ . '/' . $this->relativePath; // for container environment
        $scriptFile = sprintf('%s/%s.php', $scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (! file_exists($scriptFile)) {
            throw new Unbound($dependencyIndex); // Binding not found
        }

        /** @psalm-suppress  UnsupportedPropertyReferenceUsage */
        $singletons = &$this->singletons;
        $scriptDir = realpath(__DIR__ . '/' . $this->relativePath);

        // $scriptDir, $singletons, and $dependencyIndex can be used in the included file
        /** @var mixed $instance */
        $instance = require $scriptFile;

        /** @psalm-var T $instance */
        return $instance;
    }

    private function registerLoader(): void
    {
        $scriptDir = __DIR__ . '/' . $this->relativePath;
        if (in_array($scriptDir, self::$scriptDirs, true)) {
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

        self::$scriptDirs[] = $scriptDir;
    }

    private function getRelativePath(string $target): string
    {
        $base = explode(DIRECTORY_SEPARATOR, rtrim(__DIR__, DIRECTORY_SEPARATOR));
        $target = explode(DIRECTORY_SEPARATOR, rtrim($target, DIRECTORY_SEPARATOR));

        while (! empty($base) && ! empty($target) && $base[0] === $target[0]) {
            array_shift($base);
            array_shift($target);
        }

        return str_repeat('..' . DIRECTORY_SEPARATOR, count($base))
            . implode(DIRECTORY_SEPARATOR, $target);
    }
}

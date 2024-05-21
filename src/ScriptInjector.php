<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\Unbound;
use Ray\Di\AbstractModule;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\AssistedModule;
use Ray\Di\Bind;
use Ray\Di\Dependency;
use Ray\Di\DependencyInterface;
use Ray\Di\Name;
use Ray\Di\NullModule;
use Ray\Di\ProviderSetModule;
use ReflectionParameter;

use function assert;
use function count;
use function error_log;
use function error_reporting;
use function file_exists;
use function file_get_contents;
use function glob;
use function in_array;
use function is_bool;
use function is_dir;
use function rmdir;
use function rtrim;
use function serialize;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const E_NOTICE;

/** @deprecated */
final class ScriptInjector implements ScriptInjectorInterface
{
    /** @var AirInjector */
    private $injector;

    /** @var string */
    private $scriptDir;

    public function __construct(string $scriptDir, ?callable $lazyModule = null)
    {
        $module = $lazyModule === null ? new NullModule() : $lazyModule();
        (new Compiler())->compile($module, $scriptDir);
        $this->injector = new AirInjector($scriptDir);
        $this->scriptDir = $scriptDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        return $this->injector->getInstance($interface, $name);
    }

    public function clear(): void
    {
        $unlink = static function (string $path) use (&$unlink): void {
            foreach ((array) glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') as $f) {
                $file = (string) $f;
                is_dir($file) ? $unlink($file) : unlink($file);
                @rmdir($file);
            }
        };
        $unlink($this->scriptDir);
    }
}

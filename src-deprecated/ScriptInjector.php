<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Name;
use Ray\Di\NullModule;
use function glob;
use function is_dir;
use function rmdir;
use function rtrim;
use function unlink;
use const DIRECTORY_SEPARATOR;

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

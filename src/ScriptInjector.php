<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\Unbound;
use Ray\Di\AbstractModule;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\AssistedModule;
use Ray\Di\Bind;
use Ray\Di\Dependency;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;
use Ray\Di\NullModule;
use Ray\Di\ProviderSetModule;
use ReflectionParameter;

use function assert;
use function count;
use function file_exists;
use function glob;
use function in_array;
use function is_callable;
use function is_dir;
use function rmdir;
use function rtrim;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class ScriptInjector implements InjectorInterface
{
    public const AOP = '/_aop.txt';

    public const INSTANCE = '%s/%s.php';

    public const QUALIFIER = '%s/qualifer/%s-%s-%s';

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

    /** @var array<callable> */
    private $functions;

    /** @var ?callable */
    private $lazyModule;

    /** @var AbstractModule|null */
    private $module;

    /** @var array<string> */
    private static $scriptDirs = [];

    /** @var bool */
    private $isSerializableLazy;

    /**
     * @param string   $scriptDir  generated instance script folder path
     * @param callable $lazyModule callable variable which return AbstractModule instance
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct($scriptDir, ?callable $lazyModule = null)
    {
        $this->scriptDir = rtrim($scriptDir, '/');
        $this->lazyModule = $lazyModule;
        $this->isSerializableLazy = $lazyModule instanceof LazyModuleInterface;
        $this->registerLoader();
        $prototype =
            /**
             * @param array{0: string, 1: string, 2: string} $injectionPoint
             *
             * @return mixed
             */
            function (string $dependencyIndex, array $injectionPoint = ['', '', '']) {
                $this->ip = $injectionPoint; // @phpstan-ignore-line
                [$prototype, $singleton, $injectionPoint, $injector] = $this->functions;

                return require $this->getInstanceFile($dependencyIndex);
            };
        $singleton =
            /**
             * @param array{0: string, 1: string, 2: string} $injectionPoint
             *
             * @return mixed
             */
            function (string $dependencyIndex, $injectionPoint = ['', '', '']) {
                if (isset($this->singletons[$dependencyIndex])) {
                    return $this->singletons[$dependencyIndex];
                }

                $this->ip = $injectionPoint;
                [$prototype, $singleton, $injectionPoint, $injector] = $this->functions;

                $instance = require $this->getInstanceFile($dependencyIndex);
                $this->singletons[$dependencyIndex] = $instance;

                return $instance;
            };
        $injectionPoint = function () use ($scriptDir): InjectionPoint {
            return new InjectionPoint(
                new ReflectionParameter([$this->ip[0], $this->ip[1]], $this->ip[2]),
                $scriptDir
            );
        };
        $injector = function (): self {
            return $this;
        };
        $this->functions = [$prototype, $singleton, $injectionPoint, $injector];
    }

    /**
     * @return list<string>
     */
    public function __sleep()
    {
        if ($this->isSerializableLazy) {
            return ['scriptDir', 'singletons', 'lazyModule', 'isSerializableLazy'];
        }

        ModuleFile::save($this->scriptDir, $this->getModule());

        return ['scriptDir', 'singletons', 'isSerializableLazy'];
    }

    public function __wakeup()
    {
        if ($this->isSerializableLazy) {
            $this->__construct(
                $this->scriptDir,
                $this->lazyModule
            );

            return;
        }

        $this->__construct(
            $this->scriptDir,
            function () {
                return ModuleFile::load($this->scriptDir);
            }
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        [$prototype, $singleton, $injectionPoint, $injector] = $this->functions;
        /** @psalm-suppress UnresolvableInclude */
        $instance = require $this->getInstanceFile($dependencyIndex);
        /** @psalm-suppress UndefinedVariable */
        $isSingleton = isset($isSingleton) && $isSingleton; // @phpstan-ignore-line
        if ($isSingleton) { // @phpstan-ignore-line
            $this->singletons[$dependencyIndex] = $instance;
        }

        /**
         * @psalm-var T $instance
         * @phpstan-var mixed $instance
         */
        return $instance;
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

    public function isSingleton(string $dependencyIndex): bool
    {
        $container = $this->getModule()->getContainer()->getContainer();

        if (! isset($container[$dependencyIndex])) {
            throw new Unbound($dependencyIndex);
        }

        $dependency = $container[$dependencyIndex];

        return $dependency instanceof Dependency ? (bool) (new PrivateProperty())($dependency, 'isSingleton') : false;
    }

    private function getModule(): AbstractModule
    {
        if ($this->isSerializableLazy || is_callable($this->lazyModule)) {
            assert(is_callable($this->lazyModule));

            return $this->initModule(($this->lazyModule)());
        }

        $fileModule = ModuleFile::load($this->scriptDir);
        if ($fileModule instanceof AbstractModule) {
            return $this->initModule($fileModule);
        }

        return $this->initModule(new NullModule());
    }

    private function initModule(AbstractModule $module): AbstractModule
    {
        $this->module = (new InstallBuiltinModule())($module);

        return $this->module;
    }

    /**
     * Return compiled script file name
     */
    private function getInstanceFile(string $dependencyIndex): string
    {
        $file = sprintf(self::INSTANCE, $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (file_exists($file)) {
            return $file;
        }

        $this->compileOnDemand($dependencyIndex, $file);
        assert(file_exists($file));

        return $file;
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
                            require $file; // @codeCoverageIgnore
                        }
                    }
                }
            );
        }

        self::$scriptDirs[] = $this->scriptDir;
    }

    private function compileOnDemand(string $dependencyIndex, string $file): void
    {
        $module = $this->getModule();
        $isFirstCompile = ! file_exists($this->scriptDir . self::AOP);
        if ($isFirstCompile) {
            $this->firstCompile($module);
            if (file_exists($file)) {
                return;
            }
        }

        (new OnDemandCompiler($this->scriptDir, $module))($dependencyIndex);
    }

    private function firstCompile(AbstractModule $module): void
    {
        (new Bind($module->getContainer(), ''))->annotatedWith(ScriptDir::class)->toInstance($this->scriptDir);
        $compiler = new DiCompiler($module, $this->scriptDir);
        $compiler->savePointcuts($module->getContainer());
        $compiler->compile();
    }

    private function installBuiltInModule(AbstractModule $module): AbstractModule
    {
        $module = new DiCompileModule(true, $module);
        $module->install(new AssistedModule());
        $module->install(new ProviderSetModule());
        $module->install(new PramReaderModule());
        $hasMultiBindings = count($module->getContainer()->multiBindings);
        if ($hasMultiBindings) {
            $module->install(new MapModule());
        }

        return $module;
    }
}

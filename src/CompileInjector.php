<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\Unbound;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\Bind;
use Ray\Di\Name;
use ReflectionParameter;

use function file_exists;
use function in_array;
use function rtrim;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function touch;

final class CompileInjector implements ScriptInjectorInterface
{
    public const INSTANCE = '%s/%s.php';
    public const COMPILE_CHECK = '%s/compiled';

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

    /** @var LazyModuleInterface */
    private $lazyModule;

    /** @var array<string> */
    private static $scriptDirs = [];

    /**
     * @param string              $scriptDir  generated instance script folder path
     * @param LazyModuleInterface $lazyModule callable variable which return AbstractModule instance
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct($scriptDir, LazyModuleInterface $lazyModule)
    {
        $this->scriptDir = rtrim($scriptDir, '/');
        $this->lazyModule = $lazyModule;
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
        return ['scriptDir', 'singletons', 'lazyModule'];
    }

    public function __wakeup()
    {
        $this->__construct(
            $this->scriptDir,
            $this->lazyModule
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

    /**
     * Return compiled script file name
     */
    private function getInstanceFile(string $dependencyIndex): string
    {
        $file = sprintf(self::INSTANCE, $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (file_exists($file)) {
            return $file;
        }

        $checkFile = sprintf(self::COMPILE_CHECK, $this->scriptDir);
        if (file_exists($checkFile)) {
            throw new Unbound($dependencyIndex);
        }

        touch($checkFile);
        $this->compile();
        if (! file_exists($file)) {
            throw new Unbound($dependencyIndex);
        }

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

    public function compile(): void
    {
        $module = (new InstallBuiltinModule())(($this->lazyModule)());
        (new Bind($module->getContainer(), ''))->annotatedWith(ScriptDir::class)->toInstance($this->scriptDir);
        (new DiCompiler($module, $this->scriptDir))->compileContainer();
    }
}

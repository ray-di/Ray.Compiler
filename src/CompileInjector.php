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

/**
 * @psalm-type ScriptDir = non-empty-string
 * @psalm-type Ip = array{0: string, 1: string, 2: string}
 */
final class CompileInjector implements ScriptInjectorInterface
{
    public const INSTANCE = '%s/%s.php';
    public const COMPILE_CHECK = '%s/compiled';

    /** @var ScriptDir */
    private $scriptDir;

    /**
     * Injection Point
     *
     * [$class, $method, $parameter]
     *
     * @var Ip
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
     * @param ScriptDir           $scriptDir  generated instance script folder path
     * @param LazyModuleInterface $lazyModule callable variable which return AbstractModule instance
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct($scriptDir, LazyModuleInterface $lazyModule)
    {
        /** @var ScriptDir $scriptDir */
        $scriptDir = rtrim($scriptDir, '/');
        $this->scriptDir = $scriptDir;
        $this->lazyModule = $lazyModule;
        $this->registerLoader();
        $prototype =
            /**
             * @param Ip $injectionPoint
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
             * @param Ip $injectionPoint
             *
             * @return mixed
             */
            function (string $dependencyIndex, array $injectionPoint = ['', '', '']) {
                if (isset($this->singletons[$dependencyIndex])) {
                    return $this->singletons[$dependencyIndex];
                }

                $this->ip = $injectionPoint; // @phpstan-ignore-line
                [$prototype, $singleton, $injectionPoint, $injector] = $this->functions;

                /** @var object $instance */
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

    /** @return list<string> */
    public function __sleep()
    {
        return ['scriptDir', 'singletons', 'lazyModule'];
    }

    public function __wakeup()
    {
        new self($this->scriptDir, $this->lazyModule);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) // @phpstan-ignore-line
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if ($this->functions === null) {
            // @codeCoverageIgnoreStart
            $this->__wakeup();
            // @codeCoverageIgnoreEnd
        }

        [$prototype, $singleton, $injectionPoint, $injector] = $this->functions;
        /** @psalm-suppress UnresolvableInclude */
        $instance = require $this->getInstanceFile($dependencyIndex);
        /** @psalm-suppress UndefinedVariable */
        $isSingleton = isset($isSingleton) && $isSingleton;
        if ($isSingleton) {
            /** @var object $instance */
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
            throw new Unbound(sprintf('[%s] See compile log %s', $dependencyIndex, $this->scriptDir . '/_compile.log'));
        }

        touch($checkFile);
        $this->compile();
        if (! file_exists($file)) {
            throw new Unbound($dependencyIndex); // @codeCoverageIgnore
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
        (new FilePutContents())(sprintf('%s/_bindings.log', $this->scriptDir), (string) $module);
        (new Bind($module->getContainer(), ''))->annotatedWith(ScriptDir::class)->toInstance($this->scriptDir);
        (new DiCompiler($module, $this->scriptDir))->compileContainer();
    }
}

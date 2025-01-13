<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\FileNotWritable;
use Ray\Di\AbstractModule;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\Bind;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function rtrim;
use function sprintf;

/**
 * Compile Injector
 *
 * This injector compiles all bindings into PHP code and uses CompiledInjector internally.
 * Once compiled, all bindings must be pre-compiled explicitly.
 * Runtime compilation of unknown concrete classes is not supported.
 *
 * @psalm-type ScriptDir = non-empty-string
 * @psalm-type Ip = array{0: string, 1: string, 2: string}
 * @psalm-type Singletons = array<string, object>
 * @psalm-type Prottype = callable(string, Ip): mixed
 * @psalm-type Singleton = callable(string, Ip): mixed
 * @psalm-type InjectionPoint = callable(): InjectionPoint
 * @psalm-type Injector = callable(): InjectorInterface
 * @psalm-type InstanceFunctions = array{0: Prottype, 1: Singleton, 2: InjectionPoint, 3: Injector}
 * @psalm-type ScriptDirs = list<ScriptDir>
 */
final class CompileInjector implements ScriptInjectorInterface // @phpstan-ignore-line
{
    /** @var ScriptDir */
    private $scriptDir;

    /** @var CompiledInjector */
    private $injector;

    /**
     * @param ScriptDir           $scriptDir  generated instance script folder path
     * @param LazyModuleInterface $lazyModule callable variable which return AbstractModule instance
     */
    public function __construct(string $scriptDir, LazyModuleInterface $lazyModule)
    {
        $this->injector = new CompiledInjector($scriptDir);
        /** @var ScriptDir $scriptDir */
        $scriptDir = rtrim($scriptDir, '/');
        $this->scriptDir = $scriptDir;
        $this->compile(($lazyModule)());
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        return $this->injector->getInstance($interface, $name);
    }

    /**
     * Compiles the module and its dependencies
     *
     * @throws FileNotWritable When binding log file cannot be written.
     */
    public function compile(AbstractModule $module): void
    {
        $module = (new InstallBuiltinModule())($module);
        (new FilePutContents())(sprintf('%s/_bindings.log', $this->scriptDir), (string) $module);
        (new Bind($module->getContainer(), ''))->annotatedWith(ScriptDir::class)->toInstance($this->scriptDir);
        (new Bind($module->getContainer(), InjectorInterface::class))->to(CompiledInjector::class);
        (new Compiler())->compile($module, $this->scriptDir);
    }
}

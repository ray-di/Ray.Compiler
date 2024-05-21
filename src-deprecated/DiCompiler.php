<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

/** @deprecated  */
final class DiCompiler implements InjectorInterface
{
    /** @var AirInjector */
    private $injector;

    /** @var AbstractModule  */
    private $module;

    /** @var string  */
    private $scriptDir;

    public function __construct(AbstractModule $module, string $scriptDir)
    {
        $this->module = $module;
        $this->scriptDir = $scriptDir;
        $this->injector = new AirInjector($scriptDir);
        $injectorModule = new class ($this->injector) extends AbstractModule {
            private $injector;

            public function __construct(InjectorInterface $injector)
            {
                $this->injector = $injector;
            }

            protected function configure()
            {
                $this->bind(InjectorInterface::class)->toInstance($this->injector);
            }
        };
        $module->install($injectorModule);
        (new Compiler())->compile($module, $scriptDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        return $this->injector->getInstance($interface, $name);
    }

    /** @deprecated  */
    public function compile(): void
    {
    }

    /** @deprecated  */
    public function savePointcuts(): void
    {
    }

    /** @deprecated  */
    public function dumpGraph(): void
    {
    }
}

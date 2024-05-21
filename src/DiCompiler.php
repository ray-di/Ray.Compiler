<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

final class DiCompiler implements InjectorInterface
{
    /** @var AirInjector */
    private $injector;

    public function __construct(AbstractModule $module, string $scriptDir)
    {
        (new Compiler())->compile($module, $scriptDir);
        $this->injector = new AirInjector($scriptDir);
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

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Name;

final class CompileInjector implements ScriptInjectorInterface
{
    /** @var AirInjector  */
    private $injector;

    public function __construct(string $scriptDir, LazyModuleInterface $lazy)
    {
        $module = $lazy();
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
}

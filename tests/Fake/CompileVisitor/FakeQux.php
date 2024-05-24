<?php

namespace Ray\Compiler\CompileVisitor;

use Ray\Di\InjectorInterface;

final class FakeQux
{
    /**
     * @var FakeBaz
     */
    public $baz;

    /**
     * @var InjectorInterface
     */
    public $injector;

    public function __construct(FakeBazInterface $baz, InjectorInterface $injector)
    {
        $this->baz = $baz;
        $this->injector = $injector;
    }
}

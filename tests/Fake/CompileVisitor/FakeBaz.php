<?php

namespace Ray\Compiler\CompileVisitor;

class FakeBaz implements FakeBazInterface
{
    public $qualifers;

    public function __construct(?array $qualifers)
    {
        $this->qualifers = $qualifers;
    }
}

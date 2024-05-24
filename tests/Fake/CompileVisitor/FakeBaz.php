<?php

namespace Ray\Compiler\CompileVisitor;

class FakeBaz implements FakeBazInterface
{
    public $qualifiers;

    public function __construct(?array $qualifiers)
    {
        $this->qualifiers = $qualifiers;
    }
}

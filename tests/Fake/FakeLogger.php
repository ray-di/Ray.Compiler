<?php

namespace Ray\Compiler;

class FakeLogger implements FakeLoggerInterface
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

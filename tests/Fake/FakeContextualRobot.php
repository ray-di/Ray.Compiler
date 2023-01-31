<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeContextualRobot implements FakeRobotInterface
{
    public $context;

    public function __construct(string $context)
    {
        $this->context = $context;
    }
}

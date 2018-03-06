<?php

namespace Ray\Compiler;

use Ray\Di\ProviderInterface;

class FakeRobotProvider implements ProviderInterface
{
    public function get()
    {
        return new FakeRobot;
    }
}

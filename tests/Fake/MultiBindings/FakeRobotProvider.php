<?php

declare(strict_types=1);

namespace Ray\Compiler\MultiBindings;

use Ray\Di\ProviderInterface;

class FakeRobotProvider implements ProviderInterface
{
    public function get()
    {
        return new FakeRobot();
    }
}

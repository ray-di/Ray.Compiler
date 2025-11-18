<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeParameterQualifierConsumer
{
    public function setRobot(
        #[FakeParameterQualifier('special')] ?FakeRobotInterface $robot = null
    ): void {
    }
}

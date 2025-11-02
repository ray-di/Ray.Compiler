<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Assisted;
use Ray\Di\Di\Named;

class FakeAssistedConsumer
{
    /**
     * @return FakeRobotInterface|null
     */
    public function assistOne(
        $a,
        $b,
        #[Assisted] ?FakeRobotInterface $robot = null)
    {
        return $robot;
    }

    public function assistWithName(
        $a,
        #[Named("one")] #[Assisted] $var1 = null)
    {
        return $var1;
    }

    /**
     * @return (FakeRobotInterface|mixed|null)[]
     * @psalm-return array{0: mixed, 1: FakeRobotInterface|null}
     */
    public function assistAny(
        #[Named("one")] #[Assisted] $var2 = null,
        #[Assisted] ?FakeRobotInterface $robot = null
    ) {
        return [$var2, $robot];
    }
}

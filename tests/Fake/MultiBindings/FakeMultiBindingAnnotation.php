<?php

declare(strict_types=1);

namespace Ray\Compiler\MultiBindings;


use Ray\Di\Di\Set;
use Ray\Di\MultiBinding\Map;

final class FakeMultiBindingAnnotation
{
    public function __construct(
        #[Set(FakeEngineInterface::class)] public Map $engines,
        #[Set(FakeRobotInterface::class)] public Map $robots
    ){
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\MultiBindings;

use Ray\Di\Di\Named;

class FakeNamedEngine implements FakeEngineInterface
{
    public const NAME = 'engine_name';

    public function __construct(
        #[Named(self::NAME)] public string $name
    ) {
    }

    public function foo()
    {
    }
}

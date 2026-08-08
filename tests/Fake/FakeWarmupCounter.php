<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeWarmupCounter
{
    public static int $count = 0;

    public function __construct()
    {
        self::$count++;
    }
}

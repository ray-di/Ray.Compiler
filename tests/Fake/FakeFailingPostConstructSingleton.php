<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\PostConstruct;
use RuntimeException;

final class FakeFailingPostConstructSingleton
{
    public static int $constructorCalls = 0;
    public static int $postConstructCalls = 0;
    public bool $initialized = false;

    public function __construct()
    {
        self::$constructorCalls++;
    }

    #[PostConstruct]
    public function initialize(): void
    {
        self::$postConstructCalls++;
        if (self::$postConstructCalls === 1) {
            throw new RuntimeException('PostConstruct failed');
        }

        $this->initialized = true;
    }

    public static function reset(): void
    {
        self::$constructorCalls = 0;
        self::$postConstructCalls = 0;
    }
}

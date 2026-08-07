<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\PostConstruct;
use Ray\Di\SetContextInterface;
use RuntimeException;

final class FakeFailingSetContextSingleton implements SetContextInterface
{
    public static int $constructorCalls = 0;
    public static int $setContextCalls = 0;
    public bool $initialized = false;

    public function __construct()
    {
        self::$constructorCalls++;
    }

    #[PostConstruct]
    public function initialize(): void
    {
        $this->initialized = true;
    }

    /**
     * @inheritDoc
     */
    public function setContext($context)
    {
        self::$setContextCalls++;
        if (self::$setContextCalls === 1) {
            throw new RuntimeException('setContext failed');
        }
    }

    public static function reset(): void
    {
        self::$constructorCalls = 0;
        self::$setContextCalls = 0;
    }
}

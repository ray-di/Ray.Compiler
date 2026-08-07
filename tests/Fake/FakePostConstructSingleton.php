<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\PostConstruct;
use Ray\Di\InjectorInterface;
use RuntimeException;

use function assert;

final class FakePostConstructSingleton
{
    public static int $postConstructCalls = 0;
    public FakePostConstructDependent|null $dependent = null;

    public function __construct(private readonly InjectorInterface $injector)
    {
    }

    #[PostConstruct]
    public function initialize(): void
    {
        self::$postConstructCalls++;
        if (self::$postConstructCalls > 1) {
            throw new RuntimeException('Recursive PostConstruct call');
        }

        $dependent = $this->injector->getInstance(FakePostConstructDependent::class);
        assert($dependent instanceof FakePostConstructDependent);
        $this->dependent = $dependent;
    }

    public static function reset(): void
    {
        self::$postConstructCalls = 0;
    }
}

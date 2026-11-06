<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Closure;

/** Holds a resource PHP refuses to serialize, as a database connection does. */
class FakeUnserializableSingleton
{
    public Closure $resource;

    public function __construct()
    {
        $this->resource = static fn (): bool => true;
    }
}

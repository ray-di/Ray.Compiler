<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Attribute;
use Ray\Di\Di\Qualifier;

#[Attribute(Attribute::TARGET_PARAMETER)]
#[Qualifier]
final class FakeParameterQualifier
{
    public function __construct(
        public readonly string $value
    ) {
    }
}

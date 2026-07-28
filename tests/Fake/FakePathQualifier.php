<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Attribute;
use Ray\Di\Di\Qualifier;

/** A custom qualifier attribute: the index carries its FQCN rather than a string. */
#[Attribute(Attribute::TARGET_PARAMETER)]
#[Qualifier]
final class FakePathQualifier
{
}

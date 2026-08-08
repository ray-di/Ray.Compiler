<?php

declare(strict_types=1);

namespace Ray\Compiler\Exception;

use LogicException;

/** Thrown for an InjectionPoint-dependent singleton; the message is its dependency index and prototype scope is required. */
final class SingletonRequiresInjectionPoint extends LogicException implements ExceptionInterface
{
}

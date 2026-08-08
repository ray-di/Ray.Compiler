<?php

declare(strict_types=1);

namespace Ray\Compiler\Exception;

use LogicException;

/** Thrown when resolving an InjectionPoint without caller context; the message is the dependency index. */
final class InjectionPointNotAvailable extends LogicException implements ExceptionInterface
{
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\Exception;

use InvalidArgumentException;

/** @deprecated Nothing throws this; an unsafe qualifier byte is percent-encoded in the script file name. */
final class InvalidQualifier extends InvalidArgumentException implements ExceptionInterface
{
}

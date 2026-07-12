<?php

declare(strict_types=1);

namespace Ray\Compiler\Exception;

use RuntimeException;

/** @deprecated The compiled script functions no longer throw this exception. A missing script is a build invariant violation and surfaces as PHP 8's native catchable Error. */
final class ScriptFileNotFound extends RuntimeException implements ExceptionInterface
{
}

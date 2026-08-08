<?php

declare(strict_types=1);

namespace Ray\Compiler\Exception;

use RuntimeException;

final class SingletonsFileNotFound extends RuntimeException implements ExceptionInterface
{
}

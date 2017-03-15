<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler\Exception;

use Ray\Di\Exception\Unbound;

class NotCompiled extends Unbound implements ExceptionInterface
{
}

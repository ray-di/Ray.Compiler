<?php
/**
 * This file is part of the Ray.Compiler
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler\Exception;

use Ray\Di\Exception\Unbound;

class NotCompiled extends Unbound implements ExceptionInterface
{
}

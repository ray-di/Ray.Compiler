<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

function delete_dir($path)
{
    foreach ((array) \glob(\rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') as $file) {
        \is_dir($file) ? delete_dir($file) : \unlink($file);
        @\rmdir($file);
    }
}

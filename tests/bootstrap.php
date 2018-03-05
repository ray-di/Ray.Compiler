<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

/* @var $loader \Composer\Autoload\ClassLoader */
require \dirname(__DIR__) . '/vendor/autoload.php';

$_ENV['TMP_DIR'] = __DIR__ . '/tmp';
delete_dir($_ENV['TMP_DIR']);

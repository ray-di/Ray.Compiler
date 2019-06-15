<?php

declare(strict_types=1);

namespace Ray\Compiler;

/* @var $loader \Composer\Autoload\ClassLoader */
require \dirname(__DIR__) . '/vendor/autoload.php';

$_ENV['TMP_DIR'] = __DIR__ . '/tmp';
delete_dir($_ENV['TMP_DIR']);

<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

/* @var $loader \Composer\Autoload\ClassLoader */
use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require \dirname(__DIR__) . '/vendor/autoload.php';
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$_ENV['TMP_DIR'] = __DIR__ . '/tmp';
// cleanup
function clear($dir)
{
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        /* @var $file \SplFileInfo */
        if ($file->getFilename()[0] !== '.') {
            @\unlink($file);
        }
    }
}
clear($_ENV['TMP_DIR']);

<?php

/**
 * Build a phar whose /di directory holds the given compiled scripts.
 *
 * Run with -d phar.readonly=0; the test suite itself runs read-only.
 *
 * argv: [1] compiled script directory (flat), [2] phar file to create
 */

declare(strict_types=1);

$phar = new Phar($argv[2]);
$phar->startBuffering();
foreach (array_filter((array) glob($argv[1] . '/*')) as $file) {
    $phar->addFile($file, 'di/' . basename($file));
}

$phar->stopBuffering();

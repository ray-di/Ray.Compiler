<?php

namespace Ray\Compiler;

use function assert;
use function file_exists;

/**
 * @param string     $scriptDir
 * @param string     $filePath
 * @param array|null $ip
 *
 * @return mixed
 */
function prototype(string $scriptDir, string $filePath, ?array $ip = null) {
    $file = realpath($scriptDir) . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');
    if (!$file || !file_exists($file)) {
        throw new \RuntimeException(sprintf('File not found: %s', $filePath));
    }
    if (!str_starts_with($file, realpath($scriptDir))) {
        throw new \RuntimeException('Path traversal detected');
    }

    // $ip can be used in the included file
    return require $file;
};

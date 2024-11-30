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
    $file = $scriptDir . $filePath;
    assert(file_exists($file)); // should be resolvable

    // $ip can be used in the included file
    return require $file;
};

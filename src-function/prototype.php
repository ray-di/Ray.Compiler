<?php

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptFileNotFound;
use function file_exists;

/**
 * @param string     $scriptDir
 * @param string     $filePath
 * @param array|null $ip
 *
 * @return mixed
 */
function prototype(string $scriptDir, string $filePath, ?array $ip = null) {
    $file = $scriptDir . DIRECTORY_SEPARATOR . $filePath;
    if (! file_exists($file)) {
        throw new ScriptFileNotFound($filePath);
    }

    // $scriptDir, $Singletons and $ip can be used in the included file
    return require $file;
};

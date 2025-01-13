<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptFileNotFound;

use function file_exists;

use const DIRECTORY_SEPARATOR;

/**
 * @param array|null $ip
 *
 * @return mixed
 */
function prototype(string $scriptDir, string $dependencyIndex, string $filePath, ?array $ip = null)
{
    $file = $scriptDir . DIRECTORY_SEPARATOR . $filePath;
    if (! file_exists($file)) {
        throw new ScriptFileNotFound($filePath);
    }

    // $scriptDir, $Singletons, $dependencyIndex and $ip can be used in the included file
    return require $file;
}

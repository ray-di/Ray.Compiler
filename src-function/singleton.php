<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptFileNotFound;

use function file_exists;

use const DIRECTORY_SEPARATOR;

function singleton(string $scriptDir, array &$singletons, string $dependencyIndex, string $filePath, ?array $ip = null)
{
    if (isset($singletons[$dependencyIndex])) {
        return $singletons[$dependencyIndex];
    }

        $scriptFile = $scriptDir . DIRECTORY_SEPARATOR . $filePath;
    if (! file_exists($scriptFile)) {
        throw new ScriptFileNotFound($scriptFile);
    }

        // $scriptDir, $Singletons, $dependencyIndex and $ip can be used in the included file
        return require $scriptFile;
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use const DIRECTORY_SEPARATOR;

/**
 * Injection with singleton scope
 *
 * @param string     $scriptDir       The base directory of the script files.
 * @param array      $singletons      The singleton instance container.
 * @param string     $dependencyIndex The dependency identifier used in the script's context.
 * @param string     $filePath        The relative file path of the script to be included.
 * @param array|null $ip              An optional array for injection point to be accessible in the script.
 *
 * @return object The resolved dependency instance from the required script file.
 */
function singleton(string $scriptDir, array &$singletons, string $dependencyIndex, string $filePath, array|null $ip = null)
{
    if (isset($singletons[$dependencyIndex])) {
        return $singletons[$dependencyIndex];
    }

    $scriptFile = $scriptDir . DIRECTORY_SEPARATOR . $filePath;

    // $scriptDir, $singletons, $dependencyIndex and $ip are available to the required script.
    return require $scriptFile;
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\ScriptFileNotFound;
use Throwable;

use function file_exists;

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
 *
 * @throws ScriptFileNotFound When the script file does not exist.
 */
function singleton(string $scriptDir, array &$singletons, string $dependencyIndex, string $filePath, array|null $ip = null)
{
    if (isset($singletons[$dependencyIndex])) {
        return $singletons[$dependencyIndex];
    }

    $scriptFile = $scriptDir . DIRECTORY_SEPARATOR . $filePath;

    try {
        // $scriptDir, $singletons, $dependencyIndex and $ip are available to the required script.
        return require $scriptFile;
    } catch (Throwable $e) {
        // Check existence only on failure, so an OPcache-cached require stays stat-free on the happy path.
        if (! file_exists($scriptFile)) {
            throw new ScriptFileNotFound($scriptFile, 0, $e);
        }

        throw $e;
    }
}

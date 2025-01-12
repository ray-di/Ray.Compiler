<?php

namespace Ray\Compiler;

use function assert;
use function file_exists;

function singleton(string $scriptDir, array &$singletons, string $dependencyIndex, string $filePath, ?array $ip = null) {
        if (isset($singletons[$dependencyIndex])) {
            return $singletons[$dependencyIndex];
        }

        $scriptFile = realpath($scriptDir) . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');
        if (!$scriptFile || !file_exists($scriptFile)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        if (!str_starts_with($scriptFile, realpath($scriptDir))) {
            throw new \RuntimeException('Path traversal detected');
        }
        $instance = require $scriptFile;
        if (!is_object($instance)) {
            throw new \RuntimeException(sprintf('File %s must return an object', $filePath));
        }
        $singletons[$dependencyIndex] = $instance;

        return $instance;
    };

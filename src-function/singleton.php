<?php

namespace Ray\Compiler;

use function assert;
use function file_exists;

function singleton(string $scriptDir, array &$singletons, string $dependencyIndex, string $filePath, ?array $ip = null) {
        if (isset($singletons[$dependencyIndex])) {
            return $singletons[$dependencyIndex];
        }

        $scriptFile =  $scriptDir . $filePath;
        assert(file_exists($scriptFile)); // soothe Psalm
        /** @var object $instance */
        $instance = require $scriptFile;
        $singletons[$dependencyIndex] = $instance;

        return $instance;
    };

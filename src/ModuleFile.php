<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

use function assert;
use function error_reporting;
use function file_exists;
use function file_get_contents;
use function is_bool;
use function serialize;
use function unserialize;

use const E_NOTICE;

final class ModuleFile
{
    public const MODULE = '/_module.txt';
    /** @var bool  */
    public static $isModuleLocked = false;

    public static function load(string $scriptDir): ?AbstractModule
    {
        $modulePath = $scriptDir . self::MODULE;
        if (! file_exists($modulePath)) {
            return null;
        }

        $serialized = file_get_contents($modulePath);
        assert(! is_bool($serialized));
        $er = error_reporting(error_reporting() ^ E_NOTICE);
        $module = unserialize($serialized, ['allowed_classes' => true]);
        error_reporting($er);
        assert($module instanceof AbstractModule);

        return $module;
    }

    public static function save(string $scriptDir, AbstractModule $module): void
    {
        if (self::$isModuleLocked || file_exists($scriptDir . self::MODULE)) {
            return;
        }

        self::$isModuleLocked = true;
        (new FilePutContents())($scriptDir . self::MODULE, serialize($module));
        self::$isModuleLocked = false;
    }
}

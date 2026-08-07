<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\InvalidQualifier;

use function preg_match;
use function sprintf;
use function str_replace;

/**
 * Maps a dependency index (`Interface-qualifier`) to a script file base name
 *
 * The qualifier is arbitrary user input (`->annotatedWith()`), so a byte outside
 * `[A-Za-z0-9_.-]` (and >= 0x80) is rejected rather than sanitized: it would
 * otherwise reach the filesystem and the generated code raw.
 */
final class ScriptName
{
    private const SAFE = '/\A[A-Za-z0-9_.\-\x80-\xFF]+\z/';

    public static function forIndex(string $index): string
    {
        $name = str_replace('\\', '_', $index);
        if (preg_match(self::SAFE, $name) !== 1) {
            throw new InvalidQualifier(sprintf('Unsafe dependency index: "%s"', $index));
        }

        return $name;
    }
}

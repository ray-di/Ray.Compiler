<?php

declare(strict_types=1);

namespace Ray\Compiler;

use function ord;
use function preg_replace_callback;
use function sprintf;
use function str_replace;

/**
 * Maps a dependency index (`Interface-qualifier`) to a script file base name
 *
 * A byte other than `[A-Za-z0-9_.-]` or >= 0x80 is percent-encoded, so the name stays
 * one path segment and safe inside a quoted PHP literal.
 */
final class ScriptName
{
    private const UNSAFE_BYTE = '/[^A-Za-z0-9_.\-\x80-\xFF]/';

    public static function forIndex(string $index): string
    {
        return (string) preg_replace_callback(
            self::UNSAFE_BYTE,
            /** @param array{0: string} $matches */
            static fn (array $matches): string => sprintf('%%%02X', ord($matches[0])),
            str_replace('\\', '_', $index),
        );
    }
}

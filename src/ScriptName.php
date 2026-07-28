<?php

declare(strict_types=1);

namespace Ray\Compiler;

use function ord;
use function preg_replace_callback;
use function sprintf;
use function str_replace;

/**
 * Maps a dependency index to the base name of its compiled script
 *
 * A dependency index is `Interface-qualifier`, and the qualifier is arbitrary user input
 * (`->annotatedWith()`). Namespace separators become underscores as before; every remaining
 * byte outside `[A-Za-z0-9_.-]` is percent-encoded.
 *
 * Encoding keeps the result a single flat path segment, so a qualifier can no longer create
 * directories or walk out of the script directory, and no encoded index can collide with an
 * unencoded one. Indexes built from class names and plain qualifiers contain no encodable
 * byte and are returned unchanged.
 *
 * The `\` to `_` step is kept as it is and stays non-injective: a class-string qualifier
 * (`->annotatedWith(Foo::class)`) shares a file with the string qualifier that spells the
 * same name with underscores. That predates this mapping; encoding `\` or `_` instead would
 * rename every compiled script.
 *
 * Bytes >= 0x80 are left alone: they are legal in a PHP identifier, and no path separator
 * (`/` 0x2F, `\` 0x5C) or NUL is reachable from them, so encoding them would rename existing
 * scripts for no gain.
 */
final class ScriptName
{
    private const UNSAFE = '/[^A-Za-z0-9_.\-\x80-\xFF]/';

    public static function from(string $index): string
    {
        return (string) preg_replace_callback(
            self::UNSAFE,
            /** @param array{0: string} $matches */
            static fn (array $matches): string => sprintf('%%%02X', ord($matches[0])),
            str_replace('\\', '_', $index),
        );
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\InvalidQualifier;

#[CoversClass(ScriptName::class)]
class ScriptNameTest extends TestCase
{
    /**
     * Indexes built from class names and plain qualifiers keep the name they have today,
     * so an already compiled script directory goes on resolving.
     */
    #[DataProvider('ordinaryIndex')]
    public function testOrdinaryIndexKeepsItsCurrentName(string $index, string $expected): void
    {
        $this->assertSame($expected, ScriptName::forIndex($index));
    }

    /** @return array<string, array{string, string}> */
    public static function ordinaryIndex(): array
    {
        return [
            'unnamed binding' => ['Ray\Compiler\FakeCarInterface-', 'Ray_Compiler_FakeCarInterface-'],
            'named binding' => ['Ray\Compiler\FakeMirrorInterface-right', 'Ray_Compiler_FakeMirrorInterface-right'],
            'qualifier class' => ['-Ray\Di\Annotation\ScriptDir', '-Ray_Di_Annotation_ScriptDir'],
            'instance binding' => ['-logo', '-logo'],
            'non ascii qualifier' => ['Ray\Compiler\FakeCarInterface-色', 'Ray_Compiler_FakeCarInterface-色'],
        ];
    }

    /** An unsafe byte must be rejected, not sanitized, so it cannot reach the filesystem or the generated code. */
    #[DataProvider('unsafeIndex')]
    public function testUnsafeIndexIsRejected(string $index): void
    {
        $this->expectException(InvalidQualifier::class);
        ScriptName::forIndex($index);
    }

    /** @return array<string, array{string}> */
    public static function unsafeIndex(): array
    {
        return [
            'slash' => ['I-a/b'],
            'parent reference' => ['I-x/../../escaped'],
            'leading slash' => ['I-/etc/x'],
            'nul byte' => ["I-bad\0name"],
            'quote' => ["I-q'x"],
            'percent' => ['I-a%b'],
        ];
    }
}

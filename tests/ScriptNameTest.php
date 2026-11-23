<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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

    /** The name stays one path segment and carries no quote, so it cannot escape the script dir or a generated literal. */
    #[DataProvider('unsafeIndex')]
    public function testUnsafeByteIsPercentEncoded(string $index, string $expected): void
    {
        $this->assertSame($expected, ScriptName::forIndex($index));
    }

    /** @return array<string, array{string, string}> */
    public static function unsafeIndex(): array
    {
        return [
            'colon' => ['-Vendor\Module:keep', '-Vendor_Module%3Akeep'],
            'parent reference' => ['I-x/../../escaped', 'I-x%2F..%2F..%2Fescaped'],
            'nul byte' => ["I-bad\0name", 'I-bad%00name'],
            'quote' => ["I-q'x", 'I-q%27x'],
            'percent' => ['I-a%b', 'I-a%25b'],
        ];
    }
}

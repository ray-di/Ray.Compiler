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
        $this->assertSame($expected, ScriptName::from($index));
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

    #[DataProvider('unsafeIndex')]
    public function testUnsafeByteIsPercentEncoded(string $index, string $expected): void
    {
        $this->assertSame($expected, ScriptName::from($index));
    }

    /** The encoded name is one path segment, so it can neither nest nor walk upwards. */
    #[DataProvider('unsafeIndex')]
    public function testEncodedNameIsASinglePathSegment(string $index, string $expected): void
    {
        $this->assertSame($expected, ScriptName::from($index));
        $this->assertStringNotContainsString('/', $expected);
        $this->assertStringNotContainsString('\\', $expected);
    }

    /** @return array<string, array{string, string}> */
    public static function unsafeIndex(): array
    {
        return [
            'slash' => ['I-a/b', 'I-a%2Fb'],
            'parent reference' => ['I-x/../../escaped', 'I-x%2F..%2F..%2Fescaped'],
            'leading slash' => ['I-/etc/x', 'I-%2Fetc%2Fx'],
            'nul byte' => ["I-bad\0name", 'I-bad%00name'],
            'quote' => ["I-q'x", 'I-q%27x'],
            'percent' => ['I-a%b', 'I-a%25b'],
        ];
    }

    /** An encoded index never lands on the file of an index that needed no encoding. */
    public function testEncodedIndexNeverCollidesWithAnUnencodedOne(): void
    {
        $this->assertNotSame(ScriptName::from('I-a/b'), ScriptName::from('I-a_b'));
        $this->assertNotSame(ScriptName::from('I-a%2Fb'), ScriptName::from('I-a/b'));
    }

    /**
     * Characterises what this mapping deliberately does not fix.
     *
     * `\` to `_` predates it and is not injective, so a class-string qualifier collides with
     * the string qualifier spelling the same name with underscores. Encoding `\` or `_` to
     * close the gap would rename every compiled script, which is not worth it.
     */
    public function testBackslashAndUnderscoreStillShareAName(): void
    {
        $this->assertSame(ScriptName::from('I-Ray\Foo'), ScriptName::from('I-Ray_Foo'));
    }
}

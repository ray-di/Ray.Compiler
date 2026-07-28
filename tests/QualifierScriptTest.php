<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\InvalidQualifier;
use Ray\Compiler\Exception\Unbound;

use function is_dir;
use function mkdir;
use function sprintf;

/** A binding qualifier is arbitrary user input; an unsafe one is rejected at compile time. */
class QualifierScriptTest extends TestCase
{
    #[DataProvider('unsafeQualifier')]
    public function testUnsafeQualifierIsRejectedAtCompileTime(string $qualifier): void
    {
        $this->expectException(InvalidQualifier::class);
        (new Compiler())->compile(new FakeQualifierModule([$qualifier]), $this->scriptDir('rejected'));
    }

    /** @return array<string, array{string}> */
    public static function unsafeQualifier(): array
    {
        return [
            'slash' => ['a/b'],
            'parent reference' => ['x/../../escaped'],
            'nul byte' => ["bad\0name"],
            'quote and backslash' => ["q'.PHP_EOL.'\\"],
            'percent' => ['a%b'],
        ];
    }

    /**
     * A qualifier reaches the index through several Ray.Di mechanisms, and each one lands in
     * different generated code: constructor argument, custom attribute, provider, setter and
     * instance binding. Safe qualifiers compile and resolve through all of them.
     */
    public function testEveryQualifierPathCompilesAndResolves(): void
    {
        $scriptDir = $this->scriptDir('paths');
        (new Compiler())->compile(new FakeQualifierPathsModule(), $scriptDir);

        $injector = new CompiledInjector($scriptDir);
        $root = $injector->getInstance(FakeQualifierConsumerInterface::class);

        $this->assertInstanceOf(FakeQualifierPathsRoot::class, $root);
        $this->assertInstanceOf(FakeEngine::class, $root->engine);
        $this->assertInstanceOf(FakeEngine::class, $root->qualifierClass);
        $this->assertInstanceOf(FakeEngine::class, $root->provided);
        $this->assertInstanceOf(FakeEngine::class, $root->setter);
        $this->assertSame('value/with/slash', $root->instance);
        $this->assertSame($root->setter, $injector->getInstance(FakeEngineInterface::class, 'setter.path'));
    }

    /** A name that could never compile cannot resolve at runtime either. */
    public function testUnsafeNameAtRuntimeIsUnbound(): void
    {
        $scriptDir = $this->scriptDir('runtime');
        (new Compiler())->compile(new FakeQualifierModule(['safe']), $scriptDir);

        $this->expectException(Unbound::class);
        (new CompiledInjector($scriptDir))->getInstance(FakeEngineInterface::class, 'a/b');
    }

    /** @return non-empty-string */
    private function scriptDir(string $name): string
    {
        $dir = __DIR__ . '/tmp/qualifier/' . $name;
        deleteFiles($dir);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            self::fail(sprintf('Could not create %s', $dir));
        }

        return $dir;
    }
}

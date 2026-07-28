<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_values;
use function file_get_contents;
use function glob;
use function is_dir;
use function mkdir;
use function sprintf;

/** A binding qualifier is arbitrary user input; it must not reach the filesystem or the generated code raw. */
class QualifierScriptTest extends TestCase
{
    public function testSlashQualifierProducesFlatOutput(): void
    {
        $scriptDir = $this->scriptDir('flat');

        (new Compiler())->compile(new FakeQualifierModule(['a/b']), $scriptDir);

        $this->assertSame([], array_values(array_filter($this->entries($scriptDir), is_dir(...))));
        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-a%2Fb.php');
    }

    public function testSlashQualifierResolvesAtRuntime(): void
    {
        $scriptDir = $this->scriptDir('resolve');
        $module = new FakeQualifierModule(['a/b'], FakeSlashQualifierConsumer::class);
        (new Compiler())->compile($module, $scriptDir);

        $instance = (new CompiledInjector($scriptDir))->getInstance(FakeQualifierConsumerInterface::class);

        $this->assertInstanceOf(FakeSlashQualifierConsumer::class, $instance);
        $this->assertInstanceOf(FakeEngine::class, $instance->engine);
    }

    /**
     * A qualifier reaches the index through several Ray.Di mechanisms, and each one lands in
     * different generated code: constructor argument, custom attribute, provider, setter and
     * instance binding. All of them must stay flat and keep resolving.
     */
    public function testEveryQualifierPathStaysFlatAndResolves(): void
    {
        $scriptDir = $this->scriptDir('paths');
        (new Compiler())->compile(new FakeQualifierPathsModule(), $scriptDir);

        $this->assertSame([], array_values(array_filter($this->entries($scriptDir), is_dir(...))));
        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-ctor%2Fslash.php');
        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-prov%2Fslash.php');
        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-setter%2Fslash.php');
        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-Ray_Compiler_FakePathQualifier.php');
        $this->assertFileExists($scriptDir . '/-inst%2Fslash.php');

        $injector = new CompiledInjector($scriptDir);
        $root = $injector->getInstance(FakeQualifierConsumerInterface::class);

        $this->assertInstanceOf(FakeQualifierPathsRoot::class, $root);
        $this->assertInstanceOf(FakeEngine::class, $root->engine);
        $this->assertInstanceOf(FakeEngine::class, $root->qualifierClass);
        $this->assertInstanceOf(FakeEngine::class, $root->provided);
        $this->assertInstanceOf(FakeEngine::class, $root->setter);
        $this->assertSame('value/with/slash', $root->instance);
        $this->assertSame($root->setter, $injector->getInstance(FakeEngineInterface::class, 'setter/slash'));
    }

    /**
     * The index is both the singleton key the generated script writes and the key
     * CompiledInjector reads. Encoding the file name must not pull those two apart.
     */
    public function testQualifiedSingletonIsSharedBetweenNestedAndDirectResolution(): void
    {
        $scriptDir = $this->scriptDir('singleton');
        $module = new FakeQualifierModule(['a/b'], FakeSlashQualifierConsumer::class, true);
        (new Compiler())->compile($module, $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        $consumer = $injector->getInstance(FakeQualifierConsumerInterface::class);
        $direct = $injector->getInstance(FakeEngineInterface::class, 'a/b');

        $this->assertInstanceOf(FakeSlashQualifierConsumer::class, $consumer);
        $this->assertSame($consumer->engine, $direct);
    }

    /** Escaping the script dir needs the intermediate directory a previous compile used to create. */
    public function testRepeatedCompileNeverWritesOutsideTheScriptDir(): void
    {
        $base = __DIR__ . '/tmp/qualifier/escape';
        $scriptDir = $this->scriptDir('escape/di');

        (new Compiler())->compile(new FakeQualifierModule(['x/keep']), $scriptDir);
        (new Compiler())->compile(new FakeQualifierModule(['x/../../escaped']), $scriptDir);

        $this->assertSame([$scriptDir], $this->entries($base));
    }

    /** A NUL byte used to abort the compile with a raw ValueError from rename(). */
    public function testNulByteQualifierCompiles(): void
    {
        // Not 'nul': that is a reserved device name on Windows and cannot be a directory.
        $scriptDir = $this->scriptDir('nulbyte');

        (new Compiler())->compile(new FakeQualifierModule(["bad\0name"]), $scriptDir);

        $this->assertFileExists($scriptDir . '/Ray_Compiler_FakeEngineInterface-bad%00name.php');
    }

    /**
     * An apostrophe used to close the index literal, so what followed was compiled as PHP in a
     * scope holding $scriptDir and $singletons; a trailing backslash escaped the closing quote.
     */
    public function testQuoteOrBackslashInQualifierCannotEscapeTheGeneratedLiteral(): void
    {
        $scriptDir = $this->scriptDir('quote');
        $module = new FakeQualifierModule(
            [FakeQuoteQualifierConsumer::QUALIFIER],
            FakeQuoteQualifierConsumer::class,
        );
        (new Compiler())->compile($module, $scriptDir);

        $code = (string) file_get_contents($scriptDir . '/Ray_Compiler_FakeQualifierConsumerInterface-.php');
        $this->assertStringNotContainsString(FakeQuoteQualifierConsumer::QUALIFIER, $code);

        $instance = (new CompiledInjector($scriptDir))->getInstance(FakeQualifierConsumerInterface::class);

        $this->assertInstanceOf(FakeQuoteQualifierConsumer::class, $instance);
        $this->assertInstanceOf(FakeEngine::class, $instance->engine);
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

    /** @return list<string> */
    private function entries(string $dir): array
    {
        return array_values(array_filter((array) glob($dir . '/*')));
    }
}

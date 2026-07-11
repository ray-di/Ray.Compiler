<?php

declare(strict_types=1);

namespace Ray\Compiler;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\InjectorInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_filter;
use function array_values;
use function assert;
use function file_get_contents;
use function implode;
use function mkdir;
use function preg_replace;
use function realpath;
use function rename;
use function str_contains;
use function str_ends_with;

/**
 * Compiled scripts must not bake the compile-time absolute path: everything
 * needed to locate them is derivable from __DIR__ at require-time, so a
 * compiled script directory can be moved after compilation.
 */
class ScriptDirRelocationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    private string $buildDir;
    private string $runtimeDir;

    protected function setUp(): void
    {
        $case = $this->name() . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $this->dataName());
        $this->buildDir = __DIR__ . '/tmp/script-dir-relocation-' . $case . '-build';
        $this->runtimeDir = __DIR__ . '/tmp/script-dir-relocation-' . $case . '-runtime';
        @mkdir($this->buildDir, 0777, true);
        (new Compiler())->compile(new FakeScriptDirModule(), $this->buildDir);
    }

    public function testGeneratedScriptDirScriptContainsNoBakedPath(): void
    {
        $script = file_get_contents($this->buildDir . '/-Ray_Di_Annotation_ScriptDir.php');
        $this->assertSame("<?php\nreturn __DIR__;", $script);
    }

    /**
     * CompilerModule also toInstance()-binds the unrelated Compile::class
     * annotation (a bool) right next to ScriptDir. The __DIR__ special case
     * in Compiler::compile()'s map() callback must match on the ScriptDir
     * key only, not swallow this neighboring instance binding.
     */
    public function testCompileAnnotationInstanceBindingStillCompilesNormally(): void
    {
        $script = file_get_contents($this->buildDir . '/-Ray_Compiler_Annotation_Compile.php');
        $this->assertSame("<?php\nreturn true;", $script);
    }

    public function testNoGeneratedScriptContainsTheCompileTimePath(): void
    {
        $phpFiles = $this->findGeneratedPhpFiles($this->buildDir);
        $this->assertNotEmpty($phpFiles, 'expected at least one generated *.php script to be scanned');

        // Collect every offender instead of asserting inside the loop, so a
        // single run reports all leaking scripts, not just the first one.
        $leaking = array_values(array_filter(
            $phpFiles,
            fn (string $path): bool => str_contains((string) file_get_contents($path), $this->buildDir),
        ));

        $this->assertSame([], $leaking, 'these generated scripts must not contain the compile-time path: ' . implode(', ', $leaking));
    }

    /** @return list<string> */
    private function findGeneratedPhpFiles(string $dir): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($iterator as $file) {
            $path = (string) $file;
            if (str_ends_with($path, '.php')) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /** @return array<string, array{0: string}> */
    public static function provideBindingNames(): array
    {
        return [
            'constructor injection' => ['ctor'],
            'setter injection' => ['setter'],
            'provider' => ['provider'],
            'AOP-woven class' => ['aop'],
        ];
    }

    #[DataProvider('provideBindingNames')]
    public function testRelocatedBindingResolvesToTheRuntimeDir(string $bindingName): void
    {
        [$injector, $runtimeDir] = $this->relocateAndCreateInjector();

        $instance = $injector->getInstance(FakeScriptDirConsumerInterface::class, $bindingName);
        $this->assertInstanceOf(FakeScriptDirConsumerInterface::class, $instance);
        $this->assertSame($runtimeDir, $instance->getScriptDir());
    }

    public function testRelocatedScriptDirInstanceBindingResolvesToTheRuntimeDir(): void
    {
        [$injector, $runtimeDir] = $this->relocateAndCreateInjector();

        $scriptDir = $injector->getInstance('', ScriptDir::class);
        $this->assertSame($runtimeDir, $scriptDir);
    }

    public function testRelocatedInjectorInterfaceBindingIsUsable(): void
    {
        [$injector, $runtimeDir] = $this->relocateAndCreateInjector();

        $innerInjector = $injector->getInstance(InjectorInterface::class);
        $this->assertInstanceOf(CompiledInjector::class, $innerInjector);

        $instance = $innerInjector->getInstance(FakeScriptDirConsumerInterface::class, 'ctor');
        $this->assertSame($runtimeDir, $instance->getScriptDir());
    }

    /** @return array{0: CompiledInjector, 1: string} */
    private function relocateAndCreateInjector(): array
    {
        $this->assertTrue(rename($this->buildDir, $this->runtimeDir), 'failed to relocate the compiled script dir');

        $runtimeDir = $this->runtimeDir;
        assert($runtimeDir !== '');

        $realRuntimeDir = realpath($runtimeDir);
        $this->assertIsString($realRuntimeDir);

        return [new CompiledInjector($runtimeDir), $realRuntimeDir];
    }
}

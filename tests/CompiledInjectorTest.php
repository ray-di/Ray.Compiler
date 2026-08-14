<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\InjectionPointNotAvailable;
use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Di\Exception\Unbound;

use function escapeshellarg;
use function exec;
use function implode;
use function mkdir;
use function serialize;
use function spl_object_hash;
use function sprintf;
use function unserialize;

use const PHP_BINARY;

/** @psalm-import-type ScriptDir from CompiledInjector */
class CompiledInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    private CompiledInjector $injector;

    protected function setUp(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile(new FakeModule(), $scriptDir);
        $this->injector = new CompiledInjector($scriptDir);
    }

    public function testCompile(): void
    {
        // built in script
        $this->assertFileExists(__DIR__ . '/tmp/-Ray_Compiler_Annotation_Compile.php');
        $this->assertFileExists(__DIR__ . '/tmp/-Ray_Di_Annotation_ScriptDir.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Aop_MethodInvocation-.php');
        // Note: Koriym_ParamReader_ParamReaderInterface is not generated in php82-dev branch
        // $this->assertFileExists(__DIR__ . '/tmp/Koriym_ParamReader_ParamReaderInterface-.php');
        // Note: AssistedInterceptor renamed to AssistedInjectInterceptor in php82-dev branch
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_AssistedInjectInterceptor-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_InjectorInterface-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_MethodInvocationProvider-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_ProviderInterface-.php');
        // application binding
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Compiler_FakeCar-.php');
    }

    #[Depends('testCompile')]
    public function testGetInstance(): void
    {
        $instance = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }

    public function testInjectionPoint(): void
    {
        $instance = $this->injector->getInstance(FakeLoggerConsumer::class);
        $this->assertInstanceOf(FakeLoggerConsumer::class, $instance);
    }

    public function testInjectionPointNotAvailableWithoutConsumer(): void
    {
        $this->expectException(InjectionPointNotAvailable::class);
        $this->expectExceptionMessage(FakeLoggerInterface::class . '-' . FakeLoggerInject::class);
        $this->injector->getInstance(FakeLoggerInterface::class, FakeLoggerInject::class);
    }

    public function testSingleton(): void
    {
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testSerialize(): void
    {
        $scriptDir = __DIR__ . '/tmp/' . __FUNCTION__;
        @mkdir($scriptDir);
        (new Compiler())->compile(new FakeModule(), $scriptDir);
        $injector = new CompiledInjector($scriptDir);
        $injector = unserialize(serialize($injector));
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }

    public function testUnbound(): void
    {
        $scriptDir = __DIR__ . '/tmp/' . __FUNCTION__;
        @mkdir($scriptDir);
        $this->expectException(Unbound::class);
        $injector = new CompiledInjector($scriptDir);
        $injector->getInstance(FakeCar2::class);
    }

    #[Depends('testUnbound')]
    public function testUnboundCompileLogFile(): void
    {
        $this->expectException(Unbound::class);
        $this->assertFileExists(__DIR__ . '/tmp/_bindings.log');
        $this->injector->getInstance(FakeCar3::class);
    }

    public function testThrowsScriptDirNotReadableException(): void
    {
        $scriptDir = __DIR__ . '/not-exists';
        $this->expectException(ScriptDirNotReadable::class);
        $this->expectExceptionMessage($scriptDir);
        new CompiledInjector($scriptDir);
    }

    /**
     * realpath() returns false for phar:// paths, but compiled scripts are
     * relocatable (see ScriptDirRelocationTest) and phars can carry them.
     */
    public function testScriptDirInsidePhar(): void
    {
        $scriptDir = __DIR__ . '/tmp/' . __FUNCTION__;
        @mkdir($scriptDir);
        (new Compiler())->compile(new FakeModule(), $scriptDir);
        $pharFile = $scriptDir . '.phar'; // outside $scriptDir so the build never globs its own output
        // The test process runs with phar.readonly=1; build in a child that does not
        exec(sprintf(
            '%s -d phar.readonly=0 %s %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/script/build_phar.php'),
            escapeshellarg($scriptDir),
            escapeshellarg($pharFile),
        ), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $injector = new CompiledInjector('phar://' . $pharFile . '/di');
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }
}

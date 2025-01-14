<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Di\Exception\Unbound;

use function serialize;
use function spl_object_hash;
use function unserialize;

/** @psalm-import-type ScriptDir from CompiledInjector */
class CompiledInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    /** @var CompiledInjector $injector */
    private $injector;

    protected function setUp(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile($scriptDir, new FakeModule());
        $this->injector = new CompiledInjector($scriptDir);
    }

    public function testCompile(): void
    {
        // built in script
        $this->assertFileExists(__DIR__ . '/tmp/-Ray_Compiler_Annotation_Compile.php');
        $this->assertFileExists(__DIR__ . '/tmp/-Ray_Di_Annotation_ScriptDir.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Aop_MethodInvocation-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Koriym_ParamReader_ParamReaderInterface-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_AssistedInterceptor-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_InjectorInterface-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_MethodInvocationProvider-.php');
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Di_ProviderInterface-.php');
        // application binding
        $this->assertFileExists(__DIR__ . '/tmp/Ray_Compiler_FakeCar-.php');
    }

    /** @depends testCompile */
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

    public function testSingleton(): void
    {
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testSerialize(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        deleteFiles($scriptDir);
        (new Compiler())->compile($scriptDir, new FakeModule());
        $injector = new CompiledInjector($scriptDir);
        $injector = unserialize(serialize($injector));
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }

    public function testUnbound(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        deleteFiles($scriptDir);
        $this->expectException(Unbound::class);
        $injector = new CompiledInjector($scriptDir);
        $injector->getInstance(FakeCar2::class);
    }

    /** @depends testUnbound */
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
}

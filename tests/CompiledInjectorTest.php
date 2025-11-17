<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Di\Exception\Unbound;

use function mkdir;
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
}

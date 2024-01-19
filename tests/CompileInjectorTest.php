<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;

use function assert;
use function file_get_contents;
use function is_object;
use function serialize;
use function spl_object_hash;
use function unserialize;

class CompileInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    /** @var CompileInjector $injector */
    private $injector;

    protected function setUp(): void
    {
        $this->injector = new CompileInjector(__DIR__ . '/tmp', new FakeLazyModule());
    }

    public function testCompile(): void
    {
        $this->injector->compile();
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

    /**
     * @depends testCompile
     */
    public function testGetInstance(): void
    {
        $instance = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }

    public function testInjectopnPoint(): void
    {
        $instance = $this->injector->getInstance(FakeLoggerConsumer::class);
        $this->assertInstanceOf(FakeLoggerConsumer::class, $instance);
    }

    public function testSingleton(): void
    {
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        assert(is_object($instance1));
        assert(is_object($instance2));
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testSerialize(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        /** @var CompileInjector $injector */
        $injector = unserialize(serialize(new CompileInjector(__DIR__ . '/tmp', new FakeLazyModule())));
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCarInterface::class, $instance);
    }

    public function testUnbound(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        $this->expectException(Unbound::class);
        $injector = new CompileInjector(__DIR__ . '/tmp', new FakeUnboundModule());
        $injector->getInstance(FakeCar2::class);
    }

    /**
     * @depends testUnbound
     */
    public function testUnboundCompileLogFile(): void
    {
        $this->expectException(Unbound::class);
        $this->injector->getInstance(FakeCar2::class);
    }

    /**
     * @depends testUnboundCompileLogFile
     */
    public function testCompileFaillureLog(): void
    {
        $logFile = __DIR__ . '/tmp/_compile.log';
        $this->assertFileExists(__DIR__ . '/tmp/compiled');
        $this->assertFileExists($logFile);
        $log = (string) file_get_contents($logFile);
        $this->assertStringContainsString('Error', $log);
    }
}

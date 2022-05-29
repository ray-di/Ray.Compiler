<?php

declare(strict_types=1);

namespace Ray\Compiler;

use DateTime;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectionMethod;
use Ray\Aop\WeavedInterface;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Name;
use ReflectionParameter;

use function assert;
use function property_exists;

class DiCompilerTest extends TestCase
{
    public function testUnbound(): void
    {
        $this->expectException(Unbound::class);
        $injector = new ScriptInjector(__DIR__ . '/tmp');
        $injector->getInstance(FakeCarInterface::class);
    }

    public function testCompile(): void
    {
        $compiler = new DiCompiler(new FakeCarModule(), __DIR__ . '/tmp');
        $compiler->compile();
        $any = Name::ANY;
        $files = [
            "Ray_Compiler_FakeCarInterface-{$any}.php",
            "Ray_Compiler_FakeEngineInterface-{$any}.php",
            "Ray_Compiler_FakeHandleInterface-{$any}.php",
            "Ray_Compiler_FakeHardtopInterface-{$any}.php",
            'Ray_Compiler_FakeMirrorInterface-right.php',
            'Ray_Compiler_FakeMirrorInterface-left.php',
            "Ray_Compiler_FakeTyreInterface-{$any}.php",
        ];
        foreach ($files as $file) {
            $filePath = __DIR__ . '/tmp/' . $file;
            $this->assertFileExists($filePath, $filePath);
        }

        $injector = new ScriptInjector(__DIR__ . '/tmp');
        $car = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testsGetInstance(): void
    {
        $compiler = new DiCompiler(new FakeCarModule(), __DIR__ . '/tmp');
        $car = $compiler->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAopCompile(): void
    {
        $compiler = new DiCompiler(new FakeAopModule(), __DIR__ . '/tmp');
        $compiler->compile();
        $any = Name::ANY;
        $files = [
            "Ray_Compiler_FakeAopInterface-{$any}.php",
            "Ray_Compiler_FakeDoubleInterceptor-{$any}.php",
        ];
        foreach ($files as $file) {
            $this->assertFileExists(__DIR__ . '/tmp/' . $file);
        }

        $this->testAopCompileFile();
    }

    /**
     * @depends testAopCompile
     */
    public function testAopCompileFile(): void
    {
        $script = new ScriptInjector(__DIR__ . '/tmp');
        $instance = $script->getInstance(FakeAopInterface::class);
        assert($instance instanceof FakeAop);
        $this->assertInstanceOf(FakeAop::class, $instance);
        $this->assertInstanceOf(WeavedInterface::class, $instance);
        $result = $instance->returnSame(1);
        $expected = 2;
        $this->assertSame($expected, $result);
    }

    public function testInjectionPoint(): void
    {
        $compiler = new DiCompiler(new FakeLoggerModule(), __DIR__ . '/tmp');
        $compiler->compile();
        $injector = new ScriptInjector(__DIR__ . '/tmp');
        /** @var FakeLoggerConsumer $loggerConsumer */
        $loggerConsumer = $injector->getInstance(FakeLoggerConsumer::class);
        assert(property_exists($loggerConsumer, 'logger'));
        assert($loggerConsumer->logger instanceof FakeLogger);
        $this->assertSame(FakeLoggerConsumer::class, $loggerConsumer->logger->name);
        $this->assertSame('MEMORY', $loggerConsumer->logger->type);

        $ip = $loggerConsumer->logger->ip;
        // test ip
        $this->assertInstanceOf(ReflectionMethod::class, $ip->getMethod());
        $this->assertSame('setLogger', $ip->getMethod()->name);
        $method = $ip->getMethod();
        assert($method instanceof ReflectionMethod);
        $this->assertInstanceOf(FakeLoggerInject::class, $method->getAnnotations()[0]);
        $this->assertInstanceOf(ReflectionParameter::class, $ip->getParameter());
        $this->assertSame('logger', $ip->getParameter()->name);
    }

    public function testDump(): void
    {
        $compiler = new DiCompiler(new FakeCarModule(), __DIR__ . '/tmp');
        $compiler->dumpGraph();
        $any = Name::ANY;
        $this->assertFileExists(__DIR__ . '/tmp/graph/Ray_Compiler_FakeCarInterface-' . $any . '.html');
    }

    /**
     * @return array<int, array<int, (array<(int|string), int>|float|int|string|true|null)>>
     */
    public function instanceProvider(): array
    {
        return [
            ['bool', true],
            ['null', null],
            ['int', 1],
            ['float', 1.0],
            ['string', 'ray'],
            ['no_index_array', [1, 2]],
            ['assoc', ['a' => 1]],
        ];
    }

    /**
     * @param array<(int|string), int>|float|int|string|true|null $expected
     *
     * @dataProvider instanceProvider
     */
    public function testInstance(string $name, $expected): void
    {
        $compiler = new DiCompiler(new FakeInstanceModule(), __DIR__ . '/tmp');
        $compiler->compile();
        $injector = new ScriptInjector(__DIR__ . '/tmp');
        $result = $injector->getInstance('', $name);
        $this->assertSame($expected, $result);
        $object = $injector->getInstance('', 'object');
        $this->assertInstanceOf(DateTime::class, $object);
    }

    public function testNullObjectCompile(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        $compiler = new DiCompiler(new FakeNullBindingModule(), $scriptDir);
        $compiler->compile();
        $any = Name::ANY;
        $files = ["Ray_Compiler_FakeAopInterface-{$any}.php"];
        foreach ($files as $file) {
            $this->assertFileExists($scriptDir . '/' . $file);
        }

        $fakeAop = (new ScriptInjector($scriptDir))->getInstance(FakeAopInterface::class);
        $this->assertArrayHasKey('returnSame', $fakeAop->bindings); // @phpstan-ignore-line
    }
}

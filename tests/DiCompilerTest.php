<?php

namespace Ray\Compiler;

use Ray\Aop\WeavedInterface;
use Ray\Compiler\Exception\NotCompiled;
use Ray\Di\Name;

class DiCompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testNotCompiled()
    {
        $this->setExpectedException(NotCompiled::class);
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $injector->getInstance(FakeCarInterface::class);
    }

    public function testCompile()
    {
        $compiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $compiler->compile();
        $any = Name::ANY;
        $files = [
            "Ray_Compiler_FakeCarInterface-{$any}",
            "Ray_Compiler_FakeEngineInterface-{$any}",
            "Ray_Compiler_FakeHandleInterface-{$any}",
            "Ray_Compiler_FakeHardtopInterface-{$any}",
            'Ray_Compiler_FakeMirrorInterface-right',
            'Ray_Compiler_FakeMirrorInterface-left',
            "Ray_Compiler_FakeTyreInterface-{$any}",
        ];
        foreach ($files as $file) {
            $filePath = $_ENV['TMP_DIR'] . '/'. $file;
            $this->assertTrue(file_exists($filePath), $filePath);
        }
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $car = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testsGetInstance()
    {
        $compiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $car = $compiler->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAopCompile()
    {
        $compiler = new DiCompiler(new FakeAopModule, $_ENV['TMP_DIR']);
        $compiler->compile();
        $any = Name::ANY;
        $files = [
            "Ray_Compiler_FakeAopInterface-{$any}",
            "Ray_Compiler_FakeDoubleInterceptor-{$any}"
        ];
        foreach ($files as $file) {
            $this->assertFileExists($_ENV['TMP_DIR'] . '/' . $file);
        }

        $this->testAopCompileFile();
    }

    /**
     * @depends testAopCompile
     */
    public function testAopCompileFile()
    {
        $script = new ScriptInjector($_ENV['TMP_DIR']);
        /** @var $instance FakeAop */
        $instance = $script->getInstance(FakeAopInterface::class);
        $this->assertInstanceOf(FakeAop::class, $instance);
        $this->assertInstanceOf(WeavedInterface::class, $instance);
        $result = $instance->returnSame(1);
        $expected = 2;
        $this->assertSame($expected, $result);
    }

    public function testInjectionPoint()
    {
        $compiler = new DiCompiler(new FakeLoggerModule, $_ENV['TMP_DIR']);
        $compiler->compile();
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $loggerConsumer = $injector->getInstance(FakeLoggerConsumer::class);
        $this->assertSame('Ray\Compiler\FakeLoggerConsumer', $loggerConsumer->logger->name);
    }

    public function testDump()
    {
        $compiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $compiler->dumpGraph();
        $any = Name::ANY;
        $this->assertFileExists($_ENV['TMP_DIR'] . '/graph/Ray_Compiler_FakeCarInterface-' . $any . '.html');
    }
}

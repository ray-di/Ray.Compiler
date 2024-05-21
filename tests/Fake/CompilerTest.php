<?php

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\CompileVisitor\FakeFoo;
use Ray\Compiler\CompileVisitor\FakeFooInterface;
use Ray\Compiler\CompileVisitor\FakeFooProvider;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use function spl_object_hash;

class CompilerTest extends TestCase
{
    private $compiler;
    private $injector;
    private $scriptDir;
    
    public function setUp(): void
    {
        $this->compiler = new Compiler();
        $this->scriptDir = __DIR__ . '/tmp';
        $this->injector = new AirInjector($this->scriptDir);
    }

    public function testCompile(): void
    {
        $module = new class() extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->to(FakeFoo::class);
            }
        };

        $scripts = $this->compiler->compile($module);
        $scripts->save($this->scriptDir);
        $this->assertInstanceOf(Scripts::class, $scripts);
        $this->assertEquals(1, count($scripts));
        $instance = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertInstanceOf(FakeFoo::class, $instance);
    }

    public function testCompileSingleton(): void
    {
        $module = new class() extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->to(FakeFoo::class)->in(Scope::SINGLETON);
            }
        };

        $scripts = $this->compiler->compile($module);
        $scripts->save($this->scriptDir);
        $instance1 = $this->injector->getInstance(FakeFooInterface::class);
        $instance2 = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testToInstance(): void
    {
        $module = new class() extends AbstractModule{
            protected function configure()
            {
                $this->bind('')->annotatedWith('foo')->toInstance('foo_instance');
            }
        };

        $scripts = $this->compiler->compile($module);
        $scripts->save($this->scriptDir);
        $instance = $this->injector->getInstance('', 'foo');
        $this->assertSame('foo_instance', $instance);
    }


    public function testCompileProvider(): void
    {
        $module = new class() extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->toProvider(FakeFooProvider::class);
            }
        };

        $scripts = $this->compiler->compile($module);
        $scripts->save($this->scriptDir);
        $instance = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertInstanceOf(FakeFoo::class, $instance);
    }

    public function testCompileComplex(): void
    {
        $module = new class() extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeCarInterface::class)->to(FakeCar::class); // dependent
                $this->bind(FakeEngineInterface::class)->to(FakeEngine::class); // constructor
                $this->bind(FakeHardtopInterface::class)->to(FakeHardtop::class); // optional setter
                $this->bind(FakeTyreInterface::class)->to(FakeTyre::class); // setter
                $this->bind(FakeMirrorInterface::class)->annotatedWith('right')->to(FakeMirrorRight::class)->in(Scope::SINGLETON); // named binding
                $this->bind(FakeMirrorInterface::class)->annotatedWith('left')->to(FakeMirrorLeft::class)->in(Scope::SINGLETON); // named binding
                $this->bind('')->annotatedWith('logo')->toInstance('momo');
                $this->bind(FakeHandleInterface::class)->toProvider(FakeHandleProvider::class);
            }
        };
        $scripts = $this->compiler->compile($module);
        $scripts->save($this->scriptDir);
        $instance = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $instance);
    }
}

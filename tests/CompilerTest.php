<?php

declare(strict_types=1);

namespace Ray\Compiler;

use DateTime;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\CompileVisitor\FakeFoo;
use Ray\Compiler\CompileVisitor\FakeFooInterface;
use Ray\Compiler\CompileVisitor\FakeFooProvider;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

use function get_class;
use function spl_object_hash;

class CompilerTest extends TestCase
{
    /** @var Compiler */
    private $compiler;

    /** @var AirInjector */
    private $injector;

    /** @var string */
    private $scriptDir;

    public function setUp(): void
    {
        $this->compiler = new Compiler();
        $this->scriptDir = __DIR__ . '/tmp';
        $this->injector = new AirInjector($this->scriptDir);
    }

    public function testCompile(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->to(FakeFoo::class);
            }
        };

        $scripts = $this->compiler->compile($module, $this->scriptDir);
        $this->assertInstanceOf(Scripts::class, $scripts);
        $instance = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertInstanceOf(FakeFoo::class, $instance);
    }

    public function testCompileSingleton(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->to(FakeFoo::class)->in(Scope::SINGLETON);
            }
        };

        $this->compiler->compile($module, $this->scriptDir);
        $instance1 = $this->injector->getInstance(FakeFooInterface::class);
        $instance2 = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testToInstance(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind('')->annotatedWith('foo')->toInstance('foo_instance');
            }
        };

        $this->compiler->compile($module, $this->scriptDir);
        $instance = $this->injector->getInstance('', 'foo');
        $this->assertSame('foo_instance', $instance);
    }

    public function testCompileProvider(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->toProvider(FakeFooProvider::class);
            }
        };

        $this->compiler->compile($module, $this->scriptDir);
        $instance = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertInstanceOf(FakeFoo::class, $instance);
    }

    public function testCompileComplex(): void
    {
        $module = new class () extends AbstractModule{
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
        $this->compiler->compile($module, $this->scriptDir);
        $instance = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $instance);
    }

    public function testCompileAop(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeAopInterface::class)->to(FakeAop::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptor::class]
                );
            }
        };
        $this->compiler->compile($module, $this->scriptDir);
        $instance = $this->injector->getInstance(FakeAopInterface::class);
        $this->assertInstanceOf(FakeAop::class, $instance);
        $double = $instance->returnSame(2);
        $this->assertSame(4, $double);
    }

    public function testCompileAopDubleInterceptor(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeAopInterface::class)->to(FakeAop::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptor::class, FakeDoubleInterceptor::class]
                );
            }
        };
        $this->compiler->compile($module, $this->scriptDir);
        $instance = $this->injector->getInstance(FakeAopInterface::class);
        $this->assertInstanceOf(FakeAop::class, $instance);
        $double = $instance->returnSame(2);
        $this->assertSame(8, $double);
    }

    public function testCompileInstnce(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind()->annotatedWith('bool')->toInstance(true);
                $this->bind()->annotatedWith('null')->toInstance(null);
                $this->bind()->annotatedWith('int')->toInstance(1);
                $this->bind()->annotatedWith('float')->toInstance(1.0);
                $this->bind()->annotatedWith('string')->toInstance('ray');
                $this->bind()->annotatedWith('no_index_array')->toInstance([1, 2]);
                $this->bind()->annotatedWith('assoc')->toInstance(['a' => 1]);
                $this->bind()->annotatedWith('object')->toInstance(new DateTime());
            }
        };
        $this->compiler->compile($module, $this->scriptDir);
        $this->assertSame(true, $this->injector->getInstance('', 'bool'));
        $this->assertSame(null, $this->injector->getInstance('', 'null'));
        $this->assertSame(1, $this->injector->getInstance('', 'int'));
        $this->assertSame(1.0, $this->injector->getInstance('', 'float'));
        $this->assertSame('ray', $this->injector->getInstance('', 'string'));
        $this->assertSame([1, 2], $this->injector->getInstance('', 'no_index_array'));
        $this->assertSame(['a' => 1], $this->injector->getInstance('', 'assoc'));
        $this->assertInstanceOf(DateTime::class, $this->injector->getInstance('', 'object'));
    }

    public function testCompileNull(): void
    {
        $module = new class () extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeFooInterface::class)->toNull();
            }
        };
        $this->compiler->compile($module, $this->scriptDir);
        $nullInstance = $this->injector->getInstance(FakeFooInterface::class);
        $this->assertInstanceOf(FakeFooInterface::class, $nullInstance);
        $this->assertIsString(get_class($nullInstance));
    }
}

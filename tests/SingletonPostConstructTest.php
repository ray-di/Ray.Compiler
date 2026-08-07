<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\Scope;
use RuntimeException;

use function is_dir;
use function mkdir;

final class SingletonPostConstructTest extends TestCase
{
    private CompiledInjector $injector;

    protected function setUp(): void
    {
        $scriptDir = __DIR__ . '/tmp/singleton-post-construct';
        if (! is_dir($scriptDir)) {
            mkdir($scriptDir, 0777, true);
        }

        deleteFiles($scriptDir);
        FakeFailingPostConstructSingleton::reset();
        FakeFailingSetContextSingleton::reset();
        FakePostConstructSingleton::reset();

        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakePostConstructSingleton::class)->in(Scope::SINGLETON);
                $this->bind(FakePostConstructDependent::class);
                $this->bind(FakeFailingPostConstructSingleton::class)->in(Scope::SINGLETON);
                $this->bind(FakeFailingSetContextSingleton::class)->in(Scope::SINGLETON);
            }
        };
        (new Compiler())->compile($module, $scriptDir);
        $this->injector = new CompiledInjector($scriptDir);
    }

    public function testSingletonIsAvailableDuringPostConstruct(): void
    {
        $singleton = $this->injector->getInstance(FakePostConstructSingleton::class);
        $this->assertInstanceOf(FakePostConstructSingleton::class, $singleton);
        $this->assertInstanceOf(FakePostConstructDependent::class, $singleton->dependent);
        $this->assertSame($singleton, $singleton->dependent->singleton);
        $this->assertSame(1, FakePostConstructSingleton::$postConstructCalls);
        $this->assertSame($this->injector, $this->injector->getInstance(InjectorInterface::class));
    }

    public function testFailedPostConstructIsRemovedFromSingletonCache(): void
    {
        try {
            $this->injector->getInstance(FakeFailingPostConstructSingleton::class);
            $this->fail('The first PostConstruct call must fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('PostConstruct failed', $e->getMessage());
        }

        $singleton = $this->injector->getInstance(FakeFailingPostConstructSingleton::class);
        $this->assertInstanceOf(FakeFailingPostConstructSingleton::class, $singleton);
        $this->assertTrue($singleton->initialized);
        $this->assertSame(2, FakeFailingPostConstructSingleton::$constructorCalls);
        $this->assertSame(2, FakeFailingPostConstructSingleton::$postConstructCalls);
        $this->assertSame($singleton, $this->injector->getInstance(FakeFailingPostConstructSingleton::class));
    }

    /** setContext() runs after PostConstruct, so it shares the same rollback. */
    public function testFailedSetContextIsRemovedFromSingletonCache(): void
    {
        try {
            $this->injector->getInstance(FakeFailingSetContextSingleton::class);
            $this->fail('The first setContext() call must fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('setContext failed', $e->getMessage());
        }

        $singleton = $this->injector->getInstance(FakeFailingSetContextSingleton::class);
        $this->assertInstanceOf(FakeFailingSetContextSingleton::class, $singleton);
        $this->assertTrue($singleton->initialized);
        $this->assertSame(2, FakeFailingSetContextSingleton::$constructorCalls);
        $this->assertSame(2, FakeFailingSetContextSingleton::$setContextCalls);
        $this->assertSame($singleton, $this->injector->getInstance(FakeFailingSetContextSingleton::class));
    }
}

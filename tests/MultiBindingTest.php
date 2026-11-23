<?php

declare(strict_types=1);

namespace Ray\Compiler;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\Unbound;
use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Compiler\Fake\MultiBindings\FakeUnboundTargetMultiBindingsModule;
use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngine2;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingAnnotation;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeNamedEngine;
use Ray\Compiler\MultiBindings\FakeRobot;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithMap;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithProvider;
use Ray\Di\AbstractModule;
use Ray\Di\Exception\SetNotFound;
use Ray\Di\MultiBinder;
use Ray\Di\MultiBinding\Map;
use Ray\Di\MultiBinding\MultiBindings;
use Ray\Di\NullModule;

use function count;
use function mkdir;

class MultiBindingTest extends TestCase
{
    private CompiledInjector $injector;

    protected function setUp(): void
    {
        @mkdir(__DIR__ . '/tmp/mulit-bindings');
        $scriptDir = __DIR__ . '/tmp/mulit-bindings';
        (new Compiler())->compile(new FakeMultiBindingsModule(), $scriptDir);
        $this->injector = new CompiledInjector($scriptDir);
    }

    /** @return Map<FakeEngineInterface> */
    public function testInjectMap(): Map
    {
        /** @var FakeMultiBindingConsumer $consumer */
        $consumer = $this->injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->engines);

        return $consumer->engines;
    }

    /** @param Map<FakeEngineInterface> $map */
    #[Depends('testInjectMap')]
    public function testMapInstance(Map $map): void
    {
        $this->assertInstanceOf(FakeEngine::class, $map['one']);
        $this->assertInstanceOf(FakeEngine2::class, $map['two']);
    }

    /** @param Map<FakeEngineInterface> $map */
    #[Depends('testInjectMap')]
    public function testMapIteration(Map $map): void
    {
        $this->assertContainsOnlyInstancesOf(FakeEngineInterface::class, $map); // @phpstan-ignore-line

        $this->assertSame(3, count($map));
    }

    /** @param Map<FakeEngineInterface> $map */
    #[Depends('testInjectMap')]
    public function testIsSet(Map $map): void
    {
        $this->assertTrue(isset($map['one']));
        $this->assertTrue(isset($map['two']));
    }

    /** @param Map<FakeEngineInterface> $map */
    #[Depends('testInjectMap')]
    public function testOffsetSet(Map $map): void
    {
        $this->expectException(LogicException::class);
        $map['one'] = 1;
    }

    /** @param Map<FakeEngineInterface> $map */
    #[Depends('testInjectMap')]
    public function testOffsetUnset(Map $map): void
    {
        $this->expectException(LogicException::class);
        unset($map['one']);
    }

    public function testAnotherBinder(): void
    {
        /** @var FakeMultiBindingConsumer $consumer */
        $consumer = $this->injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->robots);
        $this->assertContainsOnlyInstancesOf(FakeRobot::class, $consumer->robots);
        $this->assertSame(3, count($consumer->robots));
    }

    public function testMultipleModule(): void
    {
        $module = new NullModule();
        $binder = MultiBinder::newInstance($module, FakeEngineInterface::class);
        $binder->addBinding('one')->to(FakeEngine::class);
        $binder->addBinding('two')->to(FakeEngine2::class);
        $module->install(new class extends AbstractModule {
            protected function configure(): void
            {
                $binder = MultiBinder::newInstance($this, FakeEngineInterface::class);
                $binder->addBinding('three')->to(FakeEngine::class);
                $binder->addBinding('four')->to(FakeEngine::class);
            }
        });
        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('two', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('three', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('four', (array) $multiBindings[FakeEngineInterface::class]);
    }

    public function testAnnotation(): void
    {
        /** @var FakeMultiBindingAnnotation $fake */
        $fake = $this->injector->getInstance(FakeMultiBindingAnnotation::class);
        $this->assertContainsOnlyInstancesOf(FakeEngineInterface::class, $fake->engines);
        $this->assertSame(3, count($fake->engines));
        $this->assertContainsOnlyInstancesOf(FakeRobotInterface::class, $fake->robots);
        $this->assertSame(3, count($fake->robots));
    }

    public function testSetNotFoundInMap(): void
    {
        $this->expectException(SetNotFound::class);
        $this->injector->getInstance(FakeSetNotFoundWithMap::class);
    }

    public function testSetNotFoundInProvider(): void
    {
        $this->expectException(SetNotFound::class);
        $this->injector->getInstance(FakeSetNotFoundWithProvider::class);
    }

    public function testUnboundTargetIsCompiled(): void
    {
        $scriptDir = $this->scriptDir('multi-bindings-unbound-target');
        (new Compiler())->compile(new FakeUnboundTargetMultiBindingsModule(), $scriptDir);

        $consumer = (new CompiledInjector($scriptDir))->getInstance(FakeMultiBindingConsumer::class);

        $this->assertInstanceOf(FakeMultiBindingConsumer::class, $consumer);
        $this->assertInstanceOf(FakeEngine::class, $consumer->engines['one']);
        $named = $consumer->engines['named'];
        $this->assertInstanceOf(FakeNamedEngine::class, $named);
        $this->assertSame('qiq', $named->name);
        $this->assertInstanceOf(FakeRobot::class, $consumer->robots['provider']);
    }

    public function testUnboundTargetDependencyFailsAtCompileTime(): void
    {
        $this->expectException(Unbound::class);
        (new Compiler())->compile(new FakeUnboundTargetMultiBindingsModule(false), $this->scriptDir('multi-bindings-unbound-dependency'));
    }

    private function scriptDir(string $name): string
    {
        $scriptDir = __DIR__ . '/tmp/' . $name;
        deleteFiles($scriptDir);
        @mkdir($scriptDir);

        return $scriptDir;
    }
}

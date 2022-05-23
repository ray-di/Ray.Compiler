<?php

declare(strict_types=1);

namespace Ray\Compiler;

use LogicException;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngine2;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingAnnotation;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeRobot;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithMap;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithProvider;
use Ray\Di\AbstractModule;
use Ray\Di\Exception\SetNotFound;
use Ray\Di\InjectorInterface;
use Ray\Di\MultiBinder;
use Ray\Di\MultiBinding\Map;
use Ray\Di\MultiBinding\MultiBindings;
use Ray\Di\NullModule;

use function count;

/**
 * @requires PHP 8.0
 */
class MultiBindingTest extends TestCase
{
    /** @var InjectorInterface */
    private $injector;

    protected function setUp(): void
    {
        $this->injector = new ScriptInjector(__DIR__ . '/tmp', static function () {
            return new FakeMultiBindingsModule();
        });
    }

    public function testInjectMap(): Map
    {
        /** @var FakeMultiBindingConsumer $consumer */
        $consumer = $this->injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->engines);

        return $consumer->engines;
    }

    /**
     * @depends testInjectMap
     */
    public function testMapInstance(Map $map): void
    {
        $this->assertInstanceOf(FakeEngine::class, $map['one']);
        $this->assertInstanceOf(FakeEngine2::class, $map['two']);
    }

    /**
     * @depends testInjectMap
     */
    public function testMapIteration(Map $map): void
    {
        $this->assertContainsOnlyInstancesOf(FakeEngineInterface::class, $map);

        $this->assertSame(3, count($map));
    }

    /**
     * @depends testInjectMap
     */
    public function testIsSet(Map $map): void
    {
        $this->assertTrue(isset($map['one']));
        $this->assertTrue(isset($map['two']));
    }

    /**
     * @depends testInjectMap
     */
    public function testOffsetSet(Map $map): void
    {
        $this->expectException(LogicException::class);
        $map['one'] = 1;
    }

    /**
     * @depends testInjectMap
     */
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

    public function testMultipileModule(): void
    {
        $module = new NullModule();
        $binder = MultiBinder::newInstance($module, FakeEngineInterface::class);
        $binder->addBinding('one')->to(FakeEngine::class);
        $binder->addBinding('two')->to(FakeEngine2::class);
        $module->install(new class extends AbstractModule {
            protected function configure()
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
}

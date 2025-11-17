<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\Fake\FakeCustomInjector;
use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeRobot;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\MultiBinder;
use Ray\Di\MultiBinding\Map;
use Ray\Di\Scope;

use function is_dir;
use function mkdir;

class CompilerModuleOverrideTest extends TestCase
{
    /**
     * Test 1: InjectorInterface replacement
     *
     * When user module binds InjectorInterface to a custom implementation,
     * CompilerModule should override it to use CompiledInjector instead.
     */
    public function testInjectorInterfaceOverride(): void
    {
        $scriptDir = __DIR__ . '/tmp/compiler-module-override';
        if (! is_dir($scriptDir)) {
            mkdir($scriptDir, 0777, true);
        }

        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                // User accidentally or intentionally binds InjectorInterface
                $this->bind(InjectorInterface::class)->to(FakeCustomInjector::class)->in(Scope::SINGLETON);
            }
        };

        (new Compiler())->compile($module, $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        // The injector should get CompiledInjector, not FakeCustomInjector
        $retrievedInjector = $injector->getInstance(InjectorInterface::class);
        $this->assertInstanceOf(CompiledInjector::class, $retrievedInjector);
    }

    /**
     * Test 2: MultiBinding with override()
     *
     * CompilerModule uses override() to replace bindings, but this should not
     * break MultiBinder functionality. MultiBindings should still work correctly.
     */
    public function testMultiBindingWithOverride(): void
    {
        $scriptDir = __DIR__ . '/tmp/multi-binding-override';
        if (! is_dir($scriptDir)) {
            mkdir($scriptDir, 0777, true);
        }

        (new Compiler())->compile(new FakeMultiBindingsModule(), $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        /** @var FakeMultiBindingConsumer $consumer */
        $consumer = $injector->getInstance(FakeMultiBindingConsumer::class);

        // Verify that MultiBinding Map was created correctly
        $this->assertInstanceOf(Map::class, $consumer->engines);

        // Verify that the map contains the expected bindings
        $engines = $consumer->engines;
        $this->assertCount(3, $engines);
        $this->assertArrayHasKey('one', $engines);
        $this->assertArrayHasKey('two', $engines);
    }

    /**
     * Test 3: Combined test - MultiBinding with InjectorInterface override
     *
     * This ensures that when a module uses both MultiBinding and binds InjectorInterface,
     * CompilerModule's override() correctly:
     * 1. Replaces InjectorInterface with CompiledInjector
     * 2. Preserves MultiBinding configurations
     */
    public function testMultiBindingWithInjectorOverride(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                // User binds InjectorInterface
                $this->bind(InjectorInterface::class)->to(FakeCustomInjector::class);

                // User also uses MultiBinding for engines
                $engineBinder = MultiBinder::newInstance($this, FakeEngineInterface::class);
                $engineBinder->addBinding('test')->to(FakeEngine::class);

                // User also uses MultiBinding for robots (required by FakeMultiBindingConsumer)
                $robotBinder = MultiBinder::newInstance($this, FakeRobotInterface::class);
                $robotBinder->addBinding('test')->to(FakeRobot::class);

                $this->bind(FakeMultiBindingConsumer::class);
                $this->bind(FakeEngine::class);
                $this->bind(FakeRobot::class);
            }
        };

        $scriptDir = __DIR__ . '/tmp/combined-override';
        if (! is_dir($scriptDir)) {
            mkdir($scriptDir, 0777, true);
        }

        (new Compiler())->compile($module, $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        // Verify InjectorInterface is CompiledInjector
        $retrievedInjector = $injector->getInstance(InjectorInterface::class);
        $this->assertInstanceOf(CompiledInjector::class, $retrievedInjector);

        // Verify MultiBinding still works
        /** @var FakeMultiBindingConsumer $consumer */
        $consumer = $injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->engines);
        $this->assertArrayHasKey('test', $consumer->engines);
    }
}

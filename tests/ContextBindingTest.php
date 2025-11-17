<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

final class ContextBindingTest extends TestCase
{
    private CompiledInjector $injector;

    public function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile(new FakeDependContextualRobotModule(''), $scriptDir);
        $this->injector = new CompiledInjector($scriptDir);
    }

    public function testContextBindingWhenContextIsEmptyAndPropertyHasType(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }

    public function testContextBindingWhenContextIsEmpty(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }
}

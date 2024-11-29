<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

final class ContextBindingTest extends TestCase
{
    /** @var CompileInjector  */
    private $injector;

    public function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        $this->injector = new CompileInjector(
            __DIR__ . '/tmp',
            new LazyModule(new FakeDependContextualRobotModule(''))
        );
    }

    /** @requires PHP >= 7.4 */
    public function testContextBindingWhenContextIsEmptyAndPropertyHasType(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }

    /** @requires PHP >= 7.4 */
    public function testContextBindingWhenContextIsEmpty(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }
}

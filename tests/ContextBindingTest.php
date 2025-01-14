<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

final class ContextBindingTest extends TestCase
{
    /** @var CompiledInjector  */
    private $injector;

    public function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile($scriptDir, new FakeDependContextualRobotModule(''));
        $this->injector = new CompiledInjector($scriptDir);
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

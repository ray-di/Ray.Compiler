<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

final class ContextBindingTest extends TestCase
{
    /** @var CompiledInjector  */
    private $injector;

    public function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile(new FakeDependContextualRobotModule(''), $scriptDir);
        $this->injector = new CompiledInjector($scriptDir);
    }

    #[RequiresPhp('>= 7.4')]
    public function testContextBindingWhenContextIsEmptyAndPropertyHasType(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }

    #[RequiresPhp('>= 7.4')]
    public function testContextBindingWhenContextIsEmpty(): void
    {
        $instance = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }
}

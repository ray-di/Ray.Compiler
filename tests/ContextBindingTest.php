<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

final class ContextBindingTest extends TestCase
{
    public function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    /** @requires PHP >= 7.4 */
    public function testContextBindingWhenContextIsEmptyAndPropertyHasType(): void
    {
        $injector = new ScriptInjector(__DIR__ . '/tmp', static function () {
            return new FakeDependContextualRobotModule('');
        });

        $instance = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }

    /** @requires PHP >= 7.4 */
    public function testContextBindingWhenContextIsEmpty(): void
    {
        $injector = new ScriptInjector(__DIR__ . '/tmp', static function () {
            return new FakeContextualModule('');
        });

        $instance = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }
}

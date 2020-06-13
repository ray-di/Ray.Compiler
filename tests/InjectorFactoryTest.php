<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

class InjectorFactoryTest extends TestCase
{
    /**
     * @return array<array<array<class-string<AbstractModule>>>>
     */
    public function dataProvider() : array
    {
        return [
            [[]], // Ray.Di Injector
            [[FakeProdModule::class]] // ScriptInjector
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array<class-string<AbstractModule>> $contextModules
     */
    public function testInject(array $contextModules) : void
    {
        $injector = InjectorFactory::getInstance(
            FakeToBindPrototypeModule::class,
            $contextModules,
            __DIR__ . '/tmp/base'
        );
        $instance = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    /**
     * @param array<class-string<AbstractModule>> $contextModules
     *
     * @dataProvider dataProvider
     */
    public function testInjectDecorateModule(array $contextModules) : void
    {
        $injector = InjectorFactory::getInstance(
            FakeToBindPrototypeModule::class,
            [FakeDevModule::class] + $contextModules,
            __DIR__ . '/tmp/dev'
        );
        $instance = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeDevRobot::class, $instance);
    }

    /**
     * @param array<class-string<AbstractModule>> $contextModules
     *
     * @dataProvider dataProvider
     */
    public function testInjectComplexModule(array $contextModules) : void
    {
        $injector = InjectorFactory::getInstance(
            FakeCarModule::class,
            $contextModules,
            __DIR__ . '/tmp/car'
        );
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $instance);
    }

    /**
     * @param array<class-string<AbstractModule>> $contextModules
     *
     * @dataProvider dataProvider
     */
    public function testInjectionPoint(array $contextModules) : void
    {
        $injector = InjectorFactory::getInstance(
            FakeLoggerModule::class,
            $contextModules,
            __DIR__ . '/tmp/logger'
        );
        $instance = $injector->getInstance(FakeLoggerConsumer::class);
        $this->assertInstanceOf(FakeLoggerConsumer::class, $instance);
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\Deep\FakeDeep;
use Ray\Compiler\Deep\FakeDemand;
use Ray\Compiler\Deep\FakeInjectorContext;
use Ray\Compiler\Deep\FakeScriptInjectorContext;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\InjectorInterface;

use function assert;

class ContextInjectorTest extends TestCase
{
    public function testGetRayInjector(): InjectorInterface
    {
        $injector = ContextInjector::getInstance(new FakeTestContext(__DIR__ . '/tmp/base'));
        $this->assertInstanceOf(Injector::class, $injector);
        $robot = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $robot);

        return $injector;
    }

    public function testGetCompileInjector(): void
    {
        $injector = ContextInjector::getInstance(new FakeProdContext(__DIR__ . '/tmp/base'));
        $this->assertInstanceOf(CompileInjector::class, $injector);
        $robot = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $robot);
    }

    /**
     * @return array<array<AbstractInjectorContext>>
     */
    public function contextProvider(): array
    {
        return [
            [new FakeInjectorContext(__DIR__ . '/tmp')],
            [new FakeScriptInjectorContext(__DIR__ . '/tmp')],
        ];
    }

    /**
     * @dataProvider contextProvider
     */
    public function testContainerIsResetWhenTheInjectorIsRetrieved(AbstractInjectorContext $context): void
    {
        $injector = ContextInjector::getInstance($context);
        $deep = $injector->getInstance(FakeDeep::class);
        assert($deep instanceof FakeDeep);
        $deep->dep->changed = true;
        $deep1 = $injector->getInstance(FakeDeep::class);
        assert($deep1 instanceof FakeDeep);
        $this->assertTrue($deep->dep->changed);
        $injector = ContextInjector::getInstance($context);
        $deep2 = $injector->getInstance(FakeDeep::class);
        assert($deep2 instanceof FakeDeep);
        $this->assertFalse($deep2->dep->changed);
        $demand = $injector->getInstance(FakeDemand::class);
        $this->assertInstanceOf(FakeDemand::class, $demand);
    }

    public function testGetOverrideInstance(): void
    {
        $overrideModule = new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(FakeRobotInterface::class)->to(FakeDevRobot::class);
            }
        };

        $injector = ContextInjector::getOverrideInstance(new FakeTestContext(__DIR__ . '/tmp/base'), $overrideModule);

        $this->assertInstanceOf(Injector::class, $injector);
        $this->assertInstanceOf(FakeDevRobot::class, $injector->getInstance(FakeRobotInterface::class));
    }
}

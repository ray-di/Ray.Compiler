<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Deep\FakeDeep;
use Ray\Compiler\Deep\FakeDemand;
use Ray\Compiler\Deep\FakeInjectorContext;
use Ray\Compiler\Deep\FakeScriptInjectorContext;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\NullCache;

use function assert;

class ContextInjectorTest extends TestCase
{
    public function testGetInstance(): void
    {
        $injector = ContextInjector::getInstance(new FakeTestContext(__DIR__ . '/tmp/base'));
        $this->assertInstanceOf(InjectorInterface::class, $injector);
        $robot = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobotInterface::class, $robot);
    }

    /**
     * Install DiCompileModule when using Script Injector
     */
    public function testGetInstanceCompile(): void
    {
        $compileContext = new class (__DIR__ . '/tmp/base') extends AbstractInjectorContext{
            public function getModule(): AbstractModule
            {
                $module = new FakeToBindPrototypeModule();
                $module->install(new DiCompileModule(true)); // script injector

                return $module;
            }

            public function getCache(): CacheProvider
            {
                return new NullCache();
            }
        };
        $injector = ContextInjector::getInstance($compileContext);
        $this->assertInstanceOf(InjectorInterface::class, $injector);
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
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\Deep\FakeDeep;
use Ray\Compiler\Deep\FakeInjectorContext;
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

    public function testContainerIsResetWhenTheInjectorIsRetrieved(): void
    {
        $context = new FakeInjectorContext(__DIR__ . '/tmp');
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
    }
}

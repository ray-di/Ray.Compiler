<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;
use Ray\Di\NullCache;

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
}

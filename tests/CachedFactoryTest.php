<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;

use function spl_object_hash;

/** @psalm-type Context = 'dev'|'prod' */
class CachedFactoryTest extends TestCase
{
    public function testInstanceCachedInStaticMemory(): void
    {
        $injector1 = $this->getInjector('dev');
        $injector2 = $this->getInjector('dev');
        $this->assertNotSame(spl_object_hash($injector1), spl_object_hash($injector2));
    }

    public function testInstanceWithSavedSingletons(): void
    {
        $injector1 = $this->getInjector('prod');
        $injector2 = $this->getInjector('prod');
        $this->assertNotSame(spl_object_hash($injector1), spl_object_hash($injector2));
        $injector2->getInstance(FakeRobotInterface::class);
    }

    /** @param Context $context */
    private function getInjector(string $context): InjectorInterface
    {
        if ($context === 'dev') {
            return CachedInjectorFactory::getInstance(
                'dev',
                __DIR__ . '/tmp/dev',
                static fn (): AbstractModule => new FakeToBindPrototypeModule(),
            );
        }

        return CachedInjectorFactory::getInstance(
            'prod',
            __DIR__ . '/tmp/prod',
            static function (): AbstractModule {
                $module = new FakeToBindSingletonModule();
                $module->install(new DiCompileModule(true));

                return $module;
            },
            [FakeRobotInterface::class], // FakeRobotInterface object is cached in an injector.
        );
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Aop\WeavedInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\NullModule;

use function assert;
use function mkdir;
use function serialize;
use function spl_object_hash;
use function unserialize;

class CompileInjectorExtendedScriptInjectorTest extends TestCase
{
    /** @var CompileInjector */
    private $injector;

    protected function setUp(): void
    {
        @mkdir(__DIR__ . '/tmp');
        $this->injector = new CompileInjector(
            __DIR__ . '/tmp',
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeCarModule();
            })
        );
    }

    public function testGetInstance(): FakeCar
    {
        $car = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);

        return $car;
    }

    /** @depends testGetInstance */
    public function testDefaultValueInjected(FakeCar $car): void
    {
        $this->assertNull($car->null);
    }

    public function testCompileException(): void
    {
        $this->expectException(Unbound::class);
        $this->injector->getInstance('invalid-class'); // @phpstan-ignore-line
    }

    public function testToPrototype(): void
    {
        $tmpDir = __DIR__ . '/tmp/testToPrototype';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToBindPrototypeModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testToSingleton(): void
    {
        $tmpDir = __DIR__ . '/tmp/testToSingleton';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToBindSingletonModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testToProviderPrototype(): void
    {
        $tmpDir = __DIR__ . '/tmp/testToProviderPrototype';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToProviderPrototypeModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testToProviderSingleton(): void
    {
        $tmpdir = __DIR__ . '/tmp/testToProviderSingleton';
        @mkdir($tmpdir);
        $injector = new CompileInjector(
            $tmpdir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToProviderSingletonModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testToInstancePrototype(): void
    {
        $tmpDir = __DIR__ . '/tmp/testToInstancePrototype';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToInstancePrototypeModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testToInstanceSingleton(): void
    {
        $tmpDir = __DIR__ . '/tmp/testToInstanceSingleton';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToInstanceSingletonModule();
            })
        );
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testSerializable(): void
    {
        $tmpDir = __DIR__ . '/tmp/testSerializable';
        @mkdir($tmpDir);
        $originalInjector = new CompileInjector(
            $tmpDir,
            new FakeLazyModule()
        );

        $injector = unserialize(serialize($originalInjector));
        assert($injector instanceof InjectorInterface);
        $car = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(CompileInjector::class, $injector);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAop(): void
    {
        $injector = $this->injector;
        $instance1 = $injector->getInstance(FakeCarInterface::class);
        $instance2 = $injector->getInstance(FakeCar::class);
        $instance3 = $injector->getInstance(FakeCar2::class);
        $this->assertInstanceOf(WeavedInterface::class, $instance1);
        $this->assertInstanceOf(WeavedInterface::class, $instance2);
        $this->assertInstanceOf(WeavedInterface::class, $instance3);
        $this->assertInstanceOf(FakeRobot::class, $instance3->robot);
    }

    public function testOnDemandSingleton(): void
    {
        $tmpDir = __DIR__ . '/tmp/testOnDemandSingleton';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(static function (): AbstractModule {
                return new FakeToBindSingletonModule();
            })
        );
        $dependSingleton1 = $injector->getInstance(FakeDependSingleton::class);
        $dependSingleton2 = $injector->getInstance(FakeDependSingleton::class);
        $hash1 = spl_object_hash($dependSingleton1->robot);
        $hash2 = spl_object_hash($dependSingleton2->robot);
        $this->assertSame($hash1, $hash2);
    }

    public function testOnDemandPrototype(): void
    {
        $this->expectException(Unbound::class); // CompileInjector does not support on-demand prototype
        $this->injector->getInstance(FakeDependPrototype::class);
    }

    public function testOptional(): void
    {
        $tmpDir = __DIR__ . '/tmp/testOptional';
        @mkdir($tmpDir);
        $this->injector = new CompileInjector(
            $tmpDir,
            LazyModule::getInstance(function (): AbstractModule {
                return new class () extends AbstractModule {
                    protected function configure(): void
                    {
                        $this->bind(FakeOptional::class);
                    }
                };
            })
        );

        $optional = $this->injector->getInstance(FakeOptional::class);
        $this->assertNull($optional->robot);
    }

    public function testDependInjector(): void
    {
        $tmpDir = __DIR__ . '/tmp/testDependInjector';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new class () extends AbstractModule {
                        protected function configure(): void
                        {
                            $this->bind(FakeFactory::class);
                        }
                    };
                }
            }
        );

        $factory = $injector->getInstance(FakeFactory::class);
        $this->assertInstanceOf(InjectorInterface::class, $factory->injector);
        $factory = $injector->getInstance(FakeFactory::class);
        $this->assertInstanceOf(InjectorInterface::class, $factory->injector);
        $this->assertInstanceOf(AirInjector::class, $factory->injector);
    }

    public function testUnbound(): void
    {
        $injector = new CompileInjector(
            __DIR__ . '/tmp',
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new NullModule();
                }
            }
        );

        $this->expectException(Unbound::class);
        $this->expectExceptionMessage('NO-CLASS-NO-NAME');
        $injector->getInstance('NO-CLASS', 'NO-NAME'); // @phpstan-ignore-line
    }

    public function testCompileOnDemand(): void
    {
        $this->expectException(Unbound::class); // FakeMirrorLeft should be bound
        $injector = new CompileInjector(
            __DIR__ . '/tmp',
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new NullModule();
                }
            }
        );
        $injector->getInstance(FakeMirrorLeft::class);
    }

    public function testCompileOnDemandAop(): void
    {
        $tmpDir = __DIR__ . '/tmp/testCompileOnDemandAop';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new FakeAopModule();
                }
            }
        );

        $aop = $injector->getInstance(FakeAopInterface::class);
        $result = $aop->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testCompileOnDemandSerialize(): void
    {
        $tmpDir = __DIR__ . '/tmp/testCompileOnDemandSerialize';
        @mkdir($tmpDir);
        $injector = new CompileInjector($tmpDir, new FakeLazyModule());
        $unserializedInjector = unserialize(serialize($injector));
        $this->assertInstanceOf(InjectorInterface::class, $unserializedInjector);
        $car = $unserializedInjector->getInstance(FakeCar::class);
        $this->assertTrue($car instanceof FakeCar);
    }

    public function testCompileOnDemandAopSerialize(): void
    {
        $tmpDir = __DIR__ . '/tmp/testCompileOnDemandAopSerialize';
        @mkdir($tmpDir);
        $injector = new CompileInjector($tmpDir, new FakeAopLazyModule());
        $aop = $injector->getInstance(FakeAopInterface::class);
        $result = $aop->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testNullObjectCompile(): InjectorInterface
    {
        $injector = new CompileInjector(
            __DIR__ . '/tmp',
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new FakeNullObjectModule();
                }
            }
        );
        $instance = $injector->getInstance(FakeTyreInterface::class);
        $this->assertInstanceOf(FakeTyreInterface::class, $instance);

        return $injector;
    }

    /**
     * @runTestsInSeparateProcesses
     * @depends testNullObjectCompile
     */
    public function testNullObjectCompileCodeRead(InjectorInterface $injector): void
    {
        $instance = $injector->getInstance(FakeTyreInterface::class);
        $this->assertInstanceOf(FakeTyreInterface::class, $instance);
    }

    public function testLazyModule(): void
    {
        $injector = new CompileInjector(
            __DIR__ . '/tmp',
            new FakeLazyModule()
        );
        $car = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testNotLazyModule(): void
    {
        $injector = new CompileInjector(__DIR__ . '/tmp', new FakeLazyModule());

        $unserializedInjector = unserialize(serialize($injector));
        $car = $unserializedInjector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testSingleton(): void
    {
        $tmpDir = __DIR__ . '/tmp/testSingleton';
        @mkdir($tmpDir);
        $injector = new CompileInjector(
            $tmpDir,
            new class () implements LazyModuleInterface {
                public function __invoke(): AbstractModule
                {
                    return new FakeToBindSingletonModule();
                }
            }
        );
        $robot = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobot::class, $robot);
    }
}

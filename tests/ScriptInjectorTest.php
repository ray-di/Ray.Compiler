<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Aop\WeavedInterface;
use Ray\Di\EmptyModule;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;

class ScriptInjectorTest extends TestCase
{
    /**
     * @var ScriptInjector
     */
    private $injector;

    public function setUp()
    {
        parent::setUp();
        delete_dir($_ENV['TMP_DIR']);
        $this->injector = new ScriptInjector($_ENV['TMP_DIR']);
    }

    public function testGetInstance()
    {
        $diCompiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $diCompiler->compile();
        $car = $this->injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);

        return $car;
    }

    /**
     * @depends testGetInstance
     *
     * @param mixed $car
     */
    public function testDefaultValueInjected($car)
    {
        $this->assertNull($car->null);
    }

    public function testCompileException()
    {
        $this->expectException(Unbound::class);
        $script = new ScriptInjector($_ENV['TMP_DIR']);
        $script->getInstance('invalid-class');
    }

    public function testToPrototype()
    {
        (new DiCompiler(new FakeToBindPrototypeModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame(\spl_object_hash($instance1), \spl_object_hash($instance2));
    }

    public function testToSingleton()
    {
        (new DiCompiler(new FakeToBindSingletonModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testToProviderPrototype()
    {
        (new DiCompiler(new FakeToProviderPrototypeModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testToProviderSingleton()
    {
        (new DiCompiler(new FakeToProviderSingletonModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testToInstancePrototype()
    {
        (new DiCompiler(new FakeToInstancePrototypeModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testToInstanceSingleton()
    {
        (new DiCompiler(new FakeToInstanceSingletonModule, $_ENV['TMP_DIR']))->compile();
        $instance1 = $this->injector->getInstance(FakeRobotInterface::class);
        $instance2 = $this->injector->getInstance(FakeRobotInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    public function testSerializable()
    {
        $diCompiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $diCompiler->compile();
        $injector = \unserialize(\serialize($this->injector));
        $car = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(ScriptInjector::class, $injector);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAop()
    {
        $compiler = new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']);
        $compiler->compile();
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $instance1 = $injector->getInstance(FakeCarInterface::class);
        $instance2 = $injector->getInstance(FakeCar::class);
        /** @var FakeCar2 $instance3 */
        $instance3 = $injector->getInstance(FakeCar2::class);
        $this->assertInstanceOf(WeavedInterface::class, $instance1);
        $this->assertInstanceOf(WeavedInterface::class, $instance2);
        $this->assertInstanceOf(WeavedInterface::class, $instance3);
        $this->assertInstanceOf(FakeRobot::class, $instance3->robot);
    }

    public function testOnDemandSingleton()
    {
        (new DiCompiler(new FakeToBindSingletonModule, $_ENV['TMP_DIR']))->compile();
        /* @var  $dependSingleton1 FakeDependSingleton */
        $dependSingleton1 = $this->injector->getInstance(FakeDependSingleton::class);
        /* @var  $dependSingleton2 FakeDependSingleton */
        $dependSingleton2 = $this->injector->getInstance(FakeDependSingleton::class);
        $hash1 = \spl_object_hash($dependSingleton1->robot);
        $hash2 = \spl_object_hash($dependSingleton2->robot);
        $this->assertSame($hash1, $hash2);
        $this->testOnDemandPrototype();
    }

    public function testOnDemandPrototype()
    {
        (new DiCompiler(new FakeCarModule, $_ENV['TMP_DIR']))->compile();
        /* @var  $fakeDependPrototype1 FakeDependPrototype */
        $fakeDependPrototype1 = $this->injector->getInstance(FakeDependPrototype::class);
        /* @var  $fakeDependPrototype2 FakeDependPrototype */
        $fakeDependPrototype2 = $this->injector->getInstance(FakeDependPrototype::class);
        $hash1 = \spl_object_hash($fakeDependPrototype1->car);
        $hash2 = \spl_object_hash($fakeDependPrototype2->car);
        $this->assertNotSame($hash1, $hash2);
    }

    public function testOptional()
    {
        /* @var $optional FakeOptional */
        $optional = $this->injector->getInstance(FakeOptional::class);
        $this->assertNull($optional->robot);
    }

    public function testDependInjector()
    {
        $diCompiler = new DiCompiler(new EmptyModule, $_ENV['TMP_DIR']);
        $diCompiler->compile();
        $factory = $diCompiler->getInstance(FakeFactory::class);
        $this->assertInstanceOf(InjectorInterface::class, $factory->injector);
        /* @var $optional FakeFactory */
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $factory = $injector->getInstance(FakeFactory::class);
        $this->assertInstanceOf(InjectorInterface::class, $factory->injector);
    }

    public function testUnbound()
    {
        $this->expectException(Unbound::class);
        $this->expectExceptionMessage('NOCLASS-NONAME');
        $injector = new ScriptInjector($_ENV['TMP_DIR']);
        $injector->getInstance('NOCLASS', 'NONAME');
    }

    public function testCompileOnDemand()
    {
        delete_dir($_ENV['TMP_DIR']);
        $injector = new ScriptInjector(
            $_ENV['TMP_DIR'],
            function () {
                return new FakeCarModule;
            }
        );
        $car = $injector->getInstance(FakeCar::class);
        $this->assertTrue($car instanceof FakeCar);
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\InjectionPointUnbound;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function file_exists;
use function file_put_contents;
use function mkdir;
use function serialize;
use function str_replace;
use function unserialize;

class AirInjectorTest extends TestCase
{
    /** @var AirInjector */
    private $injector;

    /** @var string  */
    private $scriptDir;

    protected function setUp(): void
    {
        $tmpDir = __DIR__ . '/tmp/AirInjectorTest';
        @mkdir($tmpDir);
        $this->scriptDir = $tmpDir;
        if (! file_exists($this->scriptDir)) {
            mkdir($this->scriptDir, 0777, true);
        }

        $this->injector = new AirInjector($this->scriptDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->scriptDir . '/*');
        if (is_array($files)) {
            array_map('unlink', $files);
        }
        if (file_exists($this->scriptDir)) {
            rmdir($this->scriptDir);
        }
    }

    public function testGetInstance(): void
    {
        $className = FakeTestClass::class;
        file_put_contents(
            $this->scriptDir . '/' . str_replace('\\', '_', $className) . '-' . Name::ANY . '.php',
            '<?php return new ' . $className . '();'
        );

        $instance = $this->injector->getInstance($className);
        $this->assertInstanceOf($className, $instance);
    }

    public function testGetInstanceSingleton(): void
    {
        $className = FakeTestClass::class;
        $code = '<?php $isSingleton = true; return new ' . $className . '();';
        file_put_contents(
            $this->scriptDir . '/' . str_replace('\\', '_', $className) . '-' . Name::ANY . '.php',
            $code
        );

        $instance1 = $this->injector->getInstance($className);
        $instance2 = $this->injector->getInstance($className);
        $this->assertSame($instance1, $instance2);
    }

    public function testGetInstanceWithName(): void
    {
        $className = FakeTestClass::class;
        $name = 'named';
        file_put_contents(
            $this->scriptDir . '/' . str_replace('\\', '_', $className) . '-' . $name . '.php',
            '<?php return new ' . $className . '();'
        );

        $instance = $this->injector->getInstance($className, $name);
        $this->assertInstanceOf($className, $instance);
    }

    public function estInjectionPoint(): void
    {
        // 依存クラスを作成
        $engineProviderFile = $this->scriptDir . '/FakeEngineProvider.php';
        file_put_contents($engineProviderFile, '<?php
       namespace Ray\Compiler;
       class FakeEngineProvider { 
           public function __construct($ip) {} 
       }
   ');
        require $engineProviderFile;

        $carFile = $this->scriptDir . '/FakeCar.php';
        file_put_contents($carFile, '<?php
       namespace Ray\Compiler;
       class FakeCar { 
           public function setEngine(FakeEngineProvider $engine) {} 
       }
   ');
        require $carFile;

        // エンジンプロバイダーのインジェクションコード
        $providerInstanceFile = $this->scriptDir . '/Ray_Compiler_FakeEngineProvider-' . Name::ANY . '.php';
        file_put_contents($providerInstanceFile, '<?php
       $instance = new \Ray\Compiler\FakeEngineProvider($injectionPoint());
       return $instance;
   ');

        // 車のインスタンスコード
        $carInstanceFile = $this->scriptDir . '/Ray_Compiler_FakeCar-' . Name::ANY . '.php';
        file_put_contents($carInstanceFile, '<?php
       $instance = new \Ray\Compiler\FakeCar();
       $instance->setEngine($prototype("Ray_Compiler_FakeEngineProvider-", ["Ray\\Compiler\\FakeCar", "setEngine", "engine"]));
       return $instance;
   ');

        $this->expectException(InjectionPointUnbound::class);
        $this->injector->getInstance(FakeCar::class);
    }

    public function testUnbound(): void
    {
        $this->expectException(Unbound::class);
        $this->injector->getInstance('InvalidClass'); // @phpstan-ignore-line
    }

    public function testWakeup(): void
    {
        $injector = unserialize(serialize($this->injector));
        $this->assertInstanceOf(InjectorInterface::class, $injector);
    }

    public function testWithCompiler(): void
    {
        $tmpDir = __DIR__ . '/tmp/testWithCompiler';
        @mkdir($tmpDir);
        $module = new FakeCarModule();
        (new Compiler())->compile($module, $tmpDir);
        $injector = new AirInjector($tmpDir);
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $instance);
    }
}

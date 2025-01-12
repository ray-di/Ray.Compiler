<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Compiler\Exception\ScriptDirNotReadable;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

use function array_map;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_array;
use function mkdir;
use function rmdir;
use function serialize;
use function str_replace;
use function unserialize;

class CompiledInjectorTest extends TestCase
{
    /** @var CompiledInjector */
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

        $this->injector = new CompiledInjector($this->scriptDir);
    }

    public function testConstructorWithValidDirectory(): void
    {
        $tmpDir = __DIR__ . '/tmp/validDirectoryTest';
        @mkdir($tmpDir);

        $injector = new CompiledInjector($tmpDir);
        $this->assertInstanceOf(CompiledInjector::class, $injector);

        if (file_exists($tmpDir)) {
            rmdir($tmpDir);
        }
    }

    public function testConstructorThrowsExceptionForInvalidDirectory(): void
    {
        $this->expectException(ScriptDirNotReadable::class);

        $invalidDir = __DIR__ . '/invalidDirectory';
        if (file_exists($invalidDir)) {
            rmdir($invalidDir);
        }

        new CompiledInjector($invalidDir);
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
        $injector = new CompiledInjector($tmpDir);
        $instance = $injector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $instance);
    }
}

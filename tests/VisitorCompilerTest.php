<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Di\AcceptInterface;
use Ray\Di\Container;
use Ray\Di\Instance;
use Ray\Di\Name;

use function assert;
use function str_replace;

class VisitorCompilerTest extends TestCase
{
    private CompileVisitor $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitor = new CompileVisitor(new Container());
        deleteFiles(__DIR__ . '/tmp');
    }

    public function testInstanceCompileString(): void
    {
        $dependencyInstance = new Instance('bear');
        $code = $dependencyInstance->accept($this->visitor);
        $expected = <<<'EOT'
return 'bear';
EOT;
        $this->assertSame($expected, $code);
    }

    public function testInstanceCompileInt(): void
    {
        $dependencyInstance = new Instance(1);
        $code = $dependencyInstance->accept($this->visitor);
        $expected = <<<'EOT'
return 1;
EOT;
        $this->assertSame($expected, $code);
    }

    public function testInstanceCompileArray(): void
    {
        $dependencyInstance = new Instance([1, 2, 3]);
        $code = $dependencyInstance->accept($this->visitor);
        $expected = "return unserialize('a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}');";
        $this->assertSame($expected, $code);
    }

    public function testInstanceCompileArrayWithObjects(): void
    {
        $object = new FakeEngine();
        $dependencyInstance = new Instance([$object, '__invoke']);
        $code = $dependencyInstance->accept($this->visitor);

        // Should use unserialize and not crash with var_export
        $this->assertStringContainsString('return unserialize(', $code);

        // Verify it can be executed and reconstructed
        $result = eval($code);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FakeEngine::class, $result[0]);
        $this->assertSame('__invoke', $result[1]);
    }

    public function testDependencyCompileWithArrayDefaultArgs(): void
    {
        $module = new FakeArrayDefaultModule();
        $container = $module->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeClassWithArrayDefault-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));

        // Should use unserialize for array default arguments
        $this->assertStringContainsString('unserialize(', $code);

        // Verify the instance is created correctly with array defaults
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeClassWithArrayDefault(unserialize('a:1:{s:3:"key";s:5:"value";}'), unserialize('a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}'));
return $instance;
EOT;
        $this->assertSame($this->normalizeLineEndings($expected), $this->normalizeLineEndings($code));
    }

    public function testDependencyCompile(): Container
    {
        $module = new FakeCarModule();
        $container = $module->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeCarInterface-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeCar(\Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeEngineInterface-', '/Ray_Compiler_FakeEngineInterface-.php', ['Ray\Compiler\FakeCar', '__construct', 'engine']));
$instance->setTires(\Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeTyreInterface-', '/Ray_Compiler_FakeTyreInterface-.php', ['Ray\Compiler\FakeCar', 'setTires', 'frontTyre']), \Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeTyreInterface-', '/Ray_Compiler_FakeTyreInterface-.php', ['Ray\Compiler\FakeCar', 'setTires', 'rearTyre']), NULL);
$instance->setHardtop(\Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeHardtopInterface-', '/Ray_Compiler_FakeHardtopInterface-.php', ['Ray\Compiler\FakeCar', 'setHardtop', 'hardtop']));
$instance->setMirrors(\Ray\Compiler\singleton($scriptDir, $singletons, 'Ray\\Compiler\\FakeMirrorInterface-right', '/Ray_Compiler_FakeMirrorInterface-right.php', ['Ray\Compiler\FakeCar', 'setMirrors', 'rightMirror']), \Ray\Compiler\singleton($scriptDir, $singletons, 'Ray\\Compiler\\FakeMirrorInterface-left', '/Ray_Compiler_FakeMirrorInterface-left.php', ['Ray\Compiler\FakeCar', 'setMirrors', 'leftMirror']));
$instance->setSpareMirror(\Ray\Compiler\singleton($scriptDir, $singletons, 'Ray\\Compiler\\FakeMirrorInterface-right', '/Ray_Compiler_FakeMirrorInterface-right.php', ['Ray\Compiler\FakeCar', 'setSpareMirror', 'rightMirror']));
$instance->setHandle(\Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeHandleInterface-', '/Ray_Compiler_FakeHandleInterface-.php', ['Ray\Compiler\FakeCar', 'setHandle', 'handle']));
$instance->setOil(\Ray\Compiler\prototype($scriptDir, $singletons, 'Ray\\Compiler\\FakeOilInterface-', '/Ray_Compiler_FakeOilInterface-.php', ['Ray\Compiler\FakeCar', 'setOil', 'oil']));
$instance->postConstruct();
return $instance;
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings($code),
        );

        return $container;
    }

    #[Depends('testDependencyCompile')]
    public function testDependencyProviderCompile(Container $container): void
    {
        $dependency = $container->getContainer()['Ray\Compiler\FakeHandleInterface-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeHandleProvider('momo');
$instance = $instance->get();
return $instance;
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings((string) $code),
        );
    }

    #[Depends('testDependencyCompile')]
    public function testDependencyInstanceCompile(Container $container): void
    {
        $dependency = $container->getContainer()['-logo'];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
return 'momo';
EOT;
        $this->assertSame($expected, $code);
    }

    #[Depends('testDependencyCompile')]
    public function testDependencyObjectInstanceCompile(Container $container): void
    {
        $dependency = new Instance(new FakeEngine());
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
return unserialize('O:23:"Ray\\Compiler\\FakeEngine":0:{}');
EOT;
        $normalizedCode = $this->normalizeLineEndings($code);
        $this->assertContains($normalizedCode, [
            $this->normalizeLineEndings(str_replace('\\\\', '\\', $expected)),
            $this->normalizeLineEndings($expected),
        ]);
    }

    public function testContextualProviderCompile(): void
    {
        $container = (new FakeContextualModule('context'))->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeRobotInterface-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeContextualProvider();
$instance->setContext('context');
$instance = $instance->get();
return $instance;
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings((string) $code),
        );
    }

    private function normalizeLineEndings(string $content): string
    {
        // Convert Windows (CRLF: \r\n) and old Mac (CR: \r) to Unix (LF: \n)
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}

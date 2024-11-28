<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Di\AcceptInterface;
use Ray\Di\Container;
use Ray\Di\Instance;
use Ray\Di\Name;

use function assert;
use function str_replace;

class VisitorCompilerTest extends TestCase
{
    /** @var CompileVisitor  */
    private $visitor;

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
        $expected = <<<'EOT'
return array (
  0 => 1,
  1 => 2,
  2 => 3,
);
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings($code)
        );
    }

    public function testDependencyCompile(): Container
    {
        $module = new FakeCarModule();
        $container = $module->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeCarInterface-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeCar($prototype('Ray\Compiler\FakeEngineInterface-', ['Ray\Compiler\FakeCar', '__construct', 'engine']));
$instance->setTires($prototype('Ray\Compiler\FakeTyreInterface-', ['Ray\Compiler\FakeCar', 'setTires', 'frontTyre']), $prototype('Ray\Compiler\FakeTyreInterface-', ['Ray\Compiler\FakeCar', 'setTires', 'rearTyre']), NULL);
$instance->setHardtop($prototype('Ray\Compiler\FakeHardtopInterface-', ['Ray\Compiler\FakeCar', 'setHardtop', 'hardtop']));
$instance->setMirrors($singleton('Ray\Compiler\FakeMirrorInterface-right', ['Ray\Compiler\FakeCar', 'setMirrors', 'rightMirror']), $singleton('Ray\Compiler\FakeMirrorInterface-left', ['Ray\Compiler\FakeCar', 'setMirrors', 'leftMirror']));
$instance->setSpareMirror($singleton('Ray\Compiler\FakeMirrorInterface-right', ['Ray\Compiler\FakeCar', 'setSpareMirror', 'rightMirror']));
$instance->setHandle($prototype('Ray\Compiler\FakeHandleInterface-', ['Ray\Compiler\FakeCar', 'setHandle', 'handle']));
$instance->postConstruct();
$isSingleton = false;
return $instance;
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings($code)
        );

        return $container;
    }

    /** @depends testDependencyCompile */
    public function testDependencyProviderCompile(Container $container): void
    {
        $dependency = $container->getContainer()['Ray\Compiler\FakeHandleInterface-' . Name::ANY];
        assert($dependency instanceof AcceptInterface);
        $code = $dependency->accept(new CompileVisitor($container));
        $expected = <<<'EOT'
$instance = new \Ray\Compiler\FakeHandleProvider('momo');
$isSingleton = false;
return $instance->get();
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings((string) $code)
        );
    }

    /** @depends testDependencyCompile */
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

    /** @depends testDependencyCompile */
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
$isSingleton = false;
return $instance->get();
EOT;
        $this->assertSame(
            $this->normalizeLineEndings($expected),
            $this->normalizeLineEndings((string) $code)
        );
    }

    private function normalizeLineEndings(string $content): string
    {
        // Convert Windows (CRLF: \r\n) and old Mac (CR: \r) to Unix (LF: \n)
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}

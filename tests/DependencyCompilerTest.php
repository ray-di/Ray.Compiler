<?php

namespace Ray\Compiler;

use Ray\Aop\Matcher;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\Instance;
use Ray\Di\Name;

class DependencyCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Dependency
     */
    private $dependency;

    protected function setUp()
    {
        clear($_ENV['TMP_DIR']);
        parent::setUp();
    }

    public function testInstanceCompileString()
    {
        $dependencyInstance = new Instance('bear');
        $code = (new DependencyCompiler(new Container))->compile($dependencyInstance);
        $expected = <<<'EOT'
<?php

return 'bear';
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testInstanceCompileInt()
    {
        $dependencyInstance = new Instance((int) 1);
        $code = (new DependencyCompiler(new Container))->compile($dependencyInstance);
        $expected = <<<'EOT'
<?php

return 1;
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testInstanceCompileArray()
    {
        $dependencyInstance = new Instance([1, 2, 3]);
        $code = (new DependencyCompiler(new Container))->compile($dependencyInstance);
        $expected = <<<'EOT'
<?php

return array(1, 2, 3);
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testDependencyCompile()
    {
        $container = (new FakeCarModule)->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeCarInterface-' . Name::ANY];
        $code = (new DependencyCompiler($container))->compile($dependency);
        $expectedTemplate = <<<'EOT'
<?php

namespace Ray\Di\Compiler;

$instance = new \Ray\Compiler\FakeCar($prototype('Ray\\Compiler\\FakeEngineInterface-{ANY}'));
$instance->setTires($prototype('Ray\\Compiler\\FakeTyreInterface-{ANY}'), $prototype('Ray\\Compiler\\FakeTyreInterface-{ANY}'), null);
$instance->setHardtop($prototype('Ray\\Compiler\\FakeHardtopInterface-{ANY}'));
$instance->setMirrors($singleton('Ray\\Compiler\\FakeMirrorInterface-right'), $singleton('Ray\\Compiler\\FakeMirrorInterface-left'));
$instance->setSpareMirror($singleton('Ray\\Compiler\\FakeMirrorInterface-right'));
$instance->setHandle($prototype('Ray\\Compiler\\FakeHandleInterface-{ANY}', array('Ray\\Compiler\\FakeCar', 'setHandle', 'handle')));
$instance->postConstruct();
return $instance;
EOT;
        $expected = str_replace('{ANY}', Name::ANY, $expectedTemplate);
        $this->assertSame($expected, (string) $code);
    }

    public function testDependencyProviderCompile()
    {
        $container = (new FakeCarModule())->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeHandleInterface-' . Name::ANY];
        $code = (new DependencyCompiler($container))->compile($dependency);
        $expected = <<<'EOT'
<?php

namespace Ray\Di\Compiler;

$instance = new \Ray\Compiler\FakeHandleProvider('momo');
return $instance->get();
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testDependencyInstanceCompile()
    {
        $container = (new FakeCarModule)->getContainer();
        $dependency = $container->getContainer()['-logo'];
        $code = (new DependencyCompiler($container))->compile($dependency);
        $expected = <<<'EOT'
<?php

return 'momo';
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testDependencyObjectInstanceCompile()
    {
        $container = (new FakeCarModule)->getContainer();
        $dependency = new Instance(new FakeEngine());
        $code = (new DependencyCompiler($container))->compile($dependency);
        $expected = <<<'EOT'
<?php

return unserialize('O:23:"Ray\\Compiler\\FakeEngine":0:{}');
EOT;
        $this->assertSame($expected, (string) $code);
    }

    public function testDomainException()
    {
        $this->setExpectedException(\DomainException::class);
        (new DependencyCompiler(new Container))->compile(new FakeInvalidDependency);
    }

    public function testContextualProviderCompile()
    {
        $container = (new FakeContextualModule('context'))->getContainer();
        $dependency = $container->getContainer()['Ray\Compiler\FakeRobotInterface-' . Name::ANY];
        $code = (new DependencyCompiler($container))->compile($dependency);
        $expectedTemplate = <<<'EOT'
<?php

namespace Ray\Di\Compiler;

$instance = new \Ray\Compiler\FakeContextualProvider();
$instance->setContext('context');
return $instance->get();
EOT;
        $expected = str_replace('{ANY}', Name::ANY, $expectedTemplate);
        $this->assertSame($expected, (string) $code);
    }
}

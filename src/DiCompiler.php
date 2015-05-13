<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use Koriym\Printo\Printo;
use Ray\Di\AbstractModule;
use Ray\Di\Container;
use Ray\Di\DependencyInterface;
use Ray\Di\Injector;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

final class DiCompiler implements InjectorInterface
{
    /**
     * @var string
     */
    private $scriptDir;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var DependencyCompiler
     */
    private $dependencyCompiler;

    /**
     * @var Injector
     */
    private $injector;

    /**
     * @var AbstractModule
     */
    private $module;

    /**
     * @param AbstractModule $module
     * @param string         $scriptDir
     */
    public function __construct(AbstractModule $module = null, $scriptDir = null)
    {
        $this->scriptDir = $scriptDir ?: sys_get_temp_dir();
        $this->container =  $module ? $module->getContainer() : new Container;
        $this->injector = new Injector($module, $scriptDir);
        $this->dependencyCompiler = new DependencyCompiler($this->container);
        $this->module = $module;
        $this->dependencySaver = new DependencySaver($scriptDir);
    }

    /**
     * Compile and return instance
     *
     * @param string $interface
     * @param string $name
     *
     * @return mixed
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $instance = $this->injector->getInstance($interface, $name);
        $this->compile();

        return $instance;
    }

    /**
     * Compile all dependencies in container
     */
    public function compile()
    {
        $container = $this->container->getContainer();
        foreach ($container as $dependencyIndex => $dependency) {
            if (! $dependency instanceof DependencyInterface) {
                continue;
            }
            $code = $this->dependencyCompiler->compile($dependency);
            $this->dependencySaver->__invoke($dependencyIndex, $code);
        }
        $this->savePointcuts($this->container);
    }

    public function dumpGraph()
    {
        $container = $this->container->getContainer();
        foreach ($container as $dependencyIndex => $dependency) {
            if (! $dependency instanceof DependencyInterface) {
                continue;
            }
            $instance = $dependency->inject($this->container);
            $graph = (string) (new Printo($instance))
                ->setRange(Printo::RANGE_PROPERTY)
                ->setLinkDistance(130)
                ->setCharge(-500);
            $graphDir = $this->scriptDir . '/graph/';
            if (! file_exists($graphDir)) {
                mkdir($graphDir);
            }
            $file = $graphDir . str_replace('\\', '_', $dependencyIndex) . '.html';
            file_put_contents($file, $graph);
        }
    }

    private function savePointcuts(Container $container)
    {
        $ref = (new \ReflectionProperty($container, 'pointcuts'));
        $ref->setAccessible(true);
        $pointcuts = $ref->getValue($container);
        file_put_contents($this->scriptDir . '/pointcut.txt', serialize($pointcuts));
    }
}

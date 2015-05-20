<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 *
 * taken from BuilderAbstract::PhpParser() and modified for object
 */
namespace Ray\Compiler;

use Koriym\Printo\Printo;
use Ray\Di\Container;
use Ray\Di\DependencyInterface;
use Ray\Di\Name;

final class GraphDumper
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $scriptDir;

    /**
     * @param Container $container
     * @param string    $scriptDir
     */
    public function __construct(Container $container, $scriptDir)
    {
        $this->container = $container;
        $this->scriptDir = $scriptDir;
    }

    public function __invoke()
    {
        $container = $this->container->getContainer();
        foreach ($container as $dependencyIndex => $dependency) {
            $isNorInjector =  $dependencyIndex !== 'Ray\Di\InjectorInterface-' . Name::ANY;
            if ($dependency instanceof DependencyInterface && $isNorInjector) {
                $this->write($dependency, $dependencyIndex);
            }
        }
    }

    /**
     * Write html
     *
     * @param DependencyInterface $dependency
     * @param string              $dependencyIndex
     */
    private function write(DependencyInterface $dependency, $dependencyIndex)
    {
        $instance = $dependency->inject($this->container);
        $graph = (string) (new Printo($instance))
            ->setRange(Printo::RANGE_ALL)
            ->setLinkDistance(130)
            ->setCharge(-500);
        $graphDir = $this->scriptDir . '/graph/';
        if (!file_exists($graphDir)) {
            mkdir($graphDir);
        }
        $file = $graphDir . str_replace('\\', '_', $dependencyIndex) . '.html';
        file_put_contents($file, $graph);
    }
}

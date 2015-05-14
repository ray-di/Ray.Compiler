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
use Ray\Di\InjectorInterface;

class GraphDumper
{
    private $container;

    private $scriptDir;

    public function __construct(Container $container, $scriptDir)
    {
        $this->container = $container;
        $this->scriptDir = $scriptDir;
    }

    public function __invoke()
    {
        $container = $this->container->getContainer();
        foreach ($container as $dependencyIndex => $dependency) {
            if (!$dependency instanceof DependencyInterface) {
                continue;
            }
            $instance = $dependency->inject($this->container);
            if ($instance instanceof InjectorInterface) {
                continue;
            }
            $graph = (string)(new Printo($instance))
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
}

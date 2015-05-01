<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Ray\Compiler\Exception\NotCompiled;
use Ray\Di\Bind;
use Ray\Di\Container;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectionPoint;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

class ScriptInjector implements InjectorInterface
{
    /**
     * @var string
     */
    private $scriptDir;

    /**
     * Injection Point
     *
     * [$class, $method, $parameter]
     *
     * @var array
     */
    private $ip;

    /**
     * Singleton instance container
     *
     * @var array
     */
    private $singletons = [];

    /**
     * @param string $scriptDir generated instance script folder path
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $this->registerLoader();
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex =  $interface . '-' . $name;
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }
        $instance = $this->getScriptInstance($dependencyIndex);
        if ($this->isSingleton($dependencyIndex) === true) {
            $this->singletons[$dependencyIndex] = $instance;
        }

        return $instance;
    }

    /**
     * @param string $dependencyIndex
     *
     * @return mixed
     */
    private function getScriptInstance($dependencyIndex)
    {
        if ($dependencyIndex === 'Ray\Di\InjectorInterface-*') {
            return $this;
        }
        $file = sprintf('%s/__%s.php', $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        if (! file_exists($file)) {
            return $this->onDemandCompile($dependencyIndex);
        }
        $prototype = function ($dependencyIndex, array $injectionPoint = []) {
            $this->ip = $injectionPoint;

            return $this->getScriptInstance($dependencyIndex);
        };
        $singleton = function ($dependencyIndex, array $injectionPoint = []) {
            if (isset($this->singletons[$dependencyIndex])) {
                return $this->singletons[$dependencyIndex];
            }
            $this->ip = $injectionPoint;
            $instance = $this->getScriptInstance($dependencyIndex);
            $this->singletons[$dependencyIndex] = $instance;

            return $instance;
        };
        $injection_point = function () {
            return new InjectionPoint(
                new \ReflectionParameter([$this->ip[0], $this->ip[1]], $this->ip[2]),
                new AnnotationReader
            );
        };
        $injector = function () {
            return $this;
        };

        $instance = require $file;

        return $instance;
    }

    private function registerLoader()
    {
        spl_autoload_register(function ($class) {
            $file = sprintf('%s/%s.php', $this->scriptDir, $class);
            if (file_exists($file)) {
                require $file;
            }
        });
    }

    /**
     * @param string $dependencyIndex
     *
     * @return bool
     */
    public function isSingleton($dependencyIndex)
    {
        $file = sprintf('%s/__%s.php.meta.php', $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        $meta = json_decode(file_get_contents($file));
        $isSingleton = $meta->is_singleton;

        return $isSingleton;
    }

    /**
     * Return instance with compile on demand
     *
     * @param string $dependencyIndex
     *
     * @return mixed
     */
    private function onDemandCompile($dependencyIndex)
    {
        list($class, ) = explode('-', $dependencyIndex);
        if (! class_exists($class)) {
            throw new NotCompiled($class);
        }
        $dependency = (new Bind(new Container, $class))->getBound();
        $code = (new DependencyCompiler(new Container, $this))->compile($dependency);
        (new DependencySaver($this->scriptDir))->__invoke($dependencyIndex, $code);
        try {
            return $this->getScriptInstance($dependencyIndex);
        } catch (Unbound $e) {
            throw new NotCompiled($class, 500, $e);
        }
    }

    public function __wakeup()
    {
        $this->registerLoader();
    }
}

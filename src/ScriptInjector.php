<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use Ray\Aop\Compiler;
use Ray\Compiler\Exception\ClassNotFound;
use Ray\Compiler\Exception\MetaNotFound;
use Ray\Compiler\Exception\Unbound;
use Ray\Di\Bind;
use Ray\Di\Container;
use Ray\Di\Dependency;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

final class ScriptInjector implements InjectorInterface, \Serializable
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
    private static $singletons = [];

    /**
     * @var array
     */
    private $functions;

    /**
     * @var int
     */
    private $injectorId;

    /**
     * @param string $scriptDir generated instance script folder path
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $this->injectorId = \crc32($this->scriptDir);
        $this->registerLoader();
        $prototype = function ($dependencyIndex, array $injectionPoint = []) {
            $this->ip = $injectionPoint;

            return $this->getNodeInstance($dependencyIndex);
        };
        $singleton = function ($dependencyIndex, array $injectionPoint = []) {
            if (isset(self::$singletons[$this->injectorId][$dependencyIndex])) {
                return self::$singletons[$this->injectorId][$dependencyIndex];
            }
            $this->ip = $injectionPoint;
            $instance = $this->getNodeInstance($dependencyIndex);
            self::$singletons[$this->injectorId][$dependencyIndex] = $instance;

            return $instance;
        };
        $injection_point = function () use ($scriptDir) {
            return new InjectionPoint(
                new \ReflectionParameter([$this->ip[0], $this->ip[1]], $this->ip[2]),
                $scriptDir
            );
        };
        $injector = function () {
            return $this;
        };
        $this->functions = [$prototype, $singleton, $injection_point, $injector];
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        $dependencyIndex = $interface . '-' . $name;
        if (isset(self::$singletons[$this->injectorId][$dependencyIndex])) {
            return self::$singletons[$this->injectorId][$dependencyIndex];
        }
        list($instance, $isSingleton) = $this->getRootInstance($dependencyIndex);
        if ($isSingleton) {
            self::$singletons[$this->injectorId][$dependencyIndex] = $instance;
        }

        return $instance;
    }

    public function isSingleton($dependencyIndex) : bool
    {
        $pearStyleClass = \str_replace('\\', '_', $dependencyIndex);
        $file = \sprintf(DependencySaver::META_FILE, $this->scriptDir, $pearStyleClass);
        if (! \file_exists($file)) {
            throw new MetaNotFound($dependencyIndex);
        }
        $meta = \json_decode(\file_get_contents($file));
        $isSingleton = $meta->is_singleton;

        return $isSingleton;
    }

    public function serialize() : string
    {
        return \serialize([$this->scriptDir, $this->injectorId, self::$singletons[$this->injectorId]]);
    }

    public function unserialize($serialized)
    {
        list($this->scriptDir, $this->injectorId, self::$singletons[$this->injectorId]) = \unserialize($serialized);
        $this->__construct($this->scriptDir);
    }

    /**
     * Return root object of object graph and isSingleton information
     *
     * Only root object needs the information of $isSingleton. That meta information for node object was determined
     * in compile timecalled and instatiate with singleton() method call.
     *
     * @return array [(mixed) $instance, (bool) $isSigleton]
     */
    private function getRootInstance(string $dependencyIndex) : array
    {
        list($prototype, $singleton, $injection_point, $injector) = $this->functions;

        $instance = require $this->getFileName($dependencyIndex);
        /** @var bool $is_singleton */
        $isSingleton = (isset($is_singleton) && $is_singleton) ? true : false;

        return [$instance, $isSingleton];
    }

    /**
     * Return node object of object graph
     *
     * @return mixed
     */
    private function getNodeInstance(string $dependencyIndex)
    {
        list($prototype, $singleton, $injection_point, $injector) = $this->functions;

        return require $this->getFileName($dependencyIndex);
    }

    private function getFileName(string $dependencyIndex) : string
    {
        $file = \sprintf(DependencySaver::INSTANCE_FILE, $this->scriptDir, \str_replace('\\', '_', $dependencyIndex));
        if (! \file_exists($file)) {
            $this->onDemandCompile($dependencyIndex);
        }

        return $file;
    }

    /**
     * Register autoload for AOP file
     */
    private function registerLoader() : void
    {
        \spl_autoload_register(function ($class) {
            $file = \sprintf('%s/%s.php', $this->scriptDir, $class);
            if (\file_exists($file)) {
                // @codeCoverageIgnoreStart
                require $file;
                // codeCoverageIgnoreEnd
            }
        });
    }

    /**
     * Return instance with compile on demand
     *
     * @return mixed
     */
    private function onDemandCompile(string $dependencyIndex) : void
    {
        list($class) = \explode('-', $dependencyIndex);
        if (! \class_exists($class)) {
            $e = new ClassNotFound($dependencyIndex);
            throw new Unbound($dependencyIndex, 0, $e);
        }
        $container = new Container;
        new Bind($container, $class);
        /** @var Dependency $dependency */
        $dependency = $container->getContainer()[$dependencyIndex];
        $pointCuts = $this->loadPointcuts();
        if ($pointCuts) {
            $dependency->weaveAspects(new Compiler($this->scriptDir), $pointCuts);
        }
        $code = (new DependencyCompiler(new Container, $this))->compile($dependency);
        (new DependencySaver($this->scriptDir))->__invoke($dependencyIndex, $code);
    }

    /**
     * @return array|false
     */
    private function loadPointcuts()
    {
        $pointcuts = $this->scriptDir . DiCompiler::POINT_CUT;
        if (! \file_exists($pointcuts)) {
            return false;
        }

        return  \unserialize(\file_get_contents($pointcuts));
    }
}

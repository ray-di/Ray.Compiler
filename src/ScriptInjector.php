<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use Ray\Compiler\Exception\MetaNotFound;
use Ray\Di\AbstractModule;
use Ray\Di\EmptyModule;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;

final class ScriptInjector implements InjectorInterface, \Serializable
{
    const POINT_CUT = '/metas/pointcut';
    const INSTANCE_FILE = '%s/%s.php';
    const META_FILE = '%s/metas/%s.json';
    const QUALIFIER_FILE = '%s/qualifer/%s-%s-%s';

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
     * @var array [[$class,],]
     */
    private $functions;

    /**
     * @var callable
     */
    private $lazyModule;

    /**
     * @var AbstractModule
     */
    private $module;

    /**
     * @param string   $scriptDir  generated instance script folder path
     * @param callable $lazyModule callable variable which return AbstractModule instance
     */
    public function __construct($scriptDir, callable $lazyModule = null)
    {
        $this->scriptDir = $scriptDir;
        $this->lazyModule = $lazyModule ?: function () {
            return new EmptyModule;
        };
        $this->registerLoader();
        $prototype = function ($dependencyIndex, array $injectionPoint = []) {
            $this->ip = $injectionPoint;

            return $this->getNodeInstance($dependencyIndex);
        };
        $singleton = function ($dependencyIndex, array $injectionPoint = []) {
            if (isset($this->singletons[$dependencyIndex])) {
                return $this->singletons[$dependencyIndex];
            }
            $this->ip = $injectionPoint;
            $instance = $this->getNodeInstance($dependencyIndex);
            $this->singletons[$dependencyIndex] = $instance;

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
        if (isset($this->singletons[$dependencyIndex])) {
            return $this->singletons[$dependencyIndex];
        }
        list($instance, $isSingleton) = $this->getRootInstance($dependencyIndex);
        if ($isSingleton) {
            $this->singletons[$dependencyIndex] = $instance;
        }

        return $instance;
    }

    public function isSingleton($dependencyIndex) : bool
    {
        $pearStyleClass = \str_replace('\\', '_', $dependencyIndex);
        $file = \sprintf(self::META_FILE, $this->scriptDir, $pearStyleClass);
        if (! \file_exists($file)) {
            throw new MetaNotFound($dependencyIndex);
        }
        $meta = \json_decode(\file_get_contents($file));
        $isSingleton = $meta->is_singleton;

        return $isSingleton;
    }

    public function serialize() : string
    {
        $module = $this->module instanceof AbstractModule ? $this->module : ($this->lazyModule)();
        \file_put_contents($this->scriptDir . '/module', \serialize($module));

        return \serialize([$this->scriptDir, $this->singletons]);
    }

    public function unserialize($serialized)
    {
        list($this->scriptDir, $this->singletons) = \unserialize($serialized);
        $this->__construct(
            $this->scriptDir,
            function () {
                return \unserialize(\file_get_contents($this->scriptDir . '/module'));
            }
        );
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

        $instance = require $this->getInstanceFile($dependencyIndex);
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

        return require $this->getInstanceFile($dependencyIndex);
    }

    /**
     * Return compiled script file name
     */
    private function getInstanceFile(string $dependencyIndex) : string
    {
        $file = \sprintf(self::INSTANCE_FILE, $this->scriptDir, \str_replace('\\', '_', $dependencyIndex));
        if (\file_exists($file)) {
            return $file;
        }
        if (! $this->module instanceof AbstractModule) {
            $this->module = ($this->lazyModule)();
        }
        $isFirstCompile = ! \file_exists($this->scriptDir . self::POINT_CUT);
        if ($isFirstCompile) {
            (new DiCompiler(($this->lazyModule)(), $this->scriptDir))->savePointcuts($this->module->getContainer());
        }
        (new OnDemandCompiler($this, $this->scriptDir, $this->module))($dependencyIndex);

        return $file;
    }

    /**
     * Register autoload for AOP file
     */
    private function registerLoader()
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
}

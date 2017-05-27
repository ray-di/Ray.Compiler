<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use Ray\Di\InjectionPointInterface;

final class InjectionPoint implements InjectionPointInterface
{
    /**
     * @var \ReflectionParameter
     */
    private $parameter;

    /**
     * @var string
     */
    private $scriptDir;

    public function __construct(\ReflectionParameter $parameter, $scriptDir)
    {
        $this->parameter = $parameter;
        $this->scriptDir = $scriptDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->parameter->getDeclaringFunction();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->parameter->getDeclaringClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getQualifiers()
    {
        return [$this->getQualifier()];
    }

    /**
     * {@inheritdoc}
     */
    public function getQualifier()
    {
        $file = \sprintf(
            DependencySaver::QUALIFIER_FILE,
            $this->scriptDir,
            \str_replace('\\', '_', $this->parameter->getDeclaringClass()->name),
            $this->parameter->getDeclaringFunction()->name,
            $this->parameter->name
        );
        if (\file_exists($file)) {
            return \unserialize(\file_get_contents($file));
        }
    }
}

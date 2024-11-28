<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Annotation\ScriptDir;
use Ray\Di\Di\Qualifier;
use Ray\Di\InjectionPointInterface;
use Ray\ServiceLocator\ServiceLocator;
use ReflectionParameter;

use function assert;
use function class_exists;

/** @psalm-import-type ScriptDir from CompileInjector */
final class InjectionPoint implements InjectionPointInterface
{
    /** @var ReflectionParameter */
    private $parameter;

    /** @var ScriptDir */
    private $scriptDir;

    /** @param ScriptDir $scriptDir */
    public function __construct(ReflectionParameter $parameter, string $scriptDir)
    {
        $this->parameter = $parameter;
        $this->scriptDir = $scriptDir;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): ReflectionMethod
    {
        $this->parameter = $this->getParameter();
        $class = $this->parameter->getDeclaringClass();
        $method = $this->parameter->getDeclaringFunction()->getShortName();
        assert($class instanceof \ReflectionClass);
        assert(class_exists($class->getName()));

        return new ReflectionMethod($class->getName(), $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getClass(): ReflectionClass
    {
        $class = $this->parameter->getDeclaringClass();
        assert($class instanceof \ReflectionClass);

        return new ReflectionClass($class->getName());
    }

    /**
     * {@inheritDoc}
     *
     * @return array<(object|null)>
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getQualifiers(): array
    {
        return [$this->getQualifier()];
    }

    /**
     * {@inheritDoc}
     *
     * @return object|null
     */
    public function getQualifier()
    {
        $reader = ServiceLocator::getReader();
        $annotations = $reader->getMethodAnnotations($this->getMethod());
        foreach ($annotations as $annotation) {
            $maybeQualifers = $reader->getClassAnnotations(new \ReflectionClass($annotation));
            foreach ($maybeQualifers as $maybeQualifer) {
                if ($maybeQualifer instanceof Qualifier) {
                    return $annotation;
                }
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\Qualifier;
use Ray\Di\InjectionPointInterface;
use Ray\ServiceLocator\ServiceLocator;
use ReflectionParameter;

use function assert;
use function class_exists;

final class InjectionPoint implements InjectionPointInterface
{
    /** @var ReflectionParameter */
    private $parameter;

    /** @var string */
    private $scriptDir;

    public function __construct(ReflectionParameter $parameter, string $scriptDir)
    {
        $this->parameter = $parameter;
        $this->scriptDir = $scriptDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getClass(): ReflectionClass
    {
        $class = $this->parameter->getDeclaringClass();
        assert($class instanceof \ReflectionClass);

        return new ReflectionClass($class->getName());
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return object|null
     */
    public function getQualifier()
    {
        $class = $this->parameter->getDeclaringClass();
        $method = $this->parameter->getDeclaringFunction();
        assert($class instanceof \ReflectionClass);
        $reader = ServiceLocator::getReader();
        $annotations = $reader->getMethodAnnotations($method);
        foreach ($annotations as $annotation) {
            $qualifier = $reader->getClassAnnotation(new \ReflectionClass($annotation), Qualifier::class);
            if ($qualifier instanceof Qualifier) {
                return $annotation;
            }
        }

        return null;
    }
}

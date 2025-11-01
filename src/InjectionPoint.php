<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Override;
use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\Qualifier;
use Ray\Di\InjectionPointInterface;
use ReflectionAttribute;
use ReflectionException;
use ReflectionParameter;

use function assert;
use function class_exists;
use function count;

/**
 * @psalm-import-type ScriptDir from Types
 * @psalm-import-type Ip from Types
 * @psalm-import-type IpParameters from Types
 */
final class InjectionPoint implements InjectionPointInterface
{
    /** @var ReflectionParameter */
    private $parameter;

    /** @deprecated use getInstance */
    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * @param Ip $ip
     *
     * @throws ReflectionException
     */
    public static function getInstance(array $ip): self
    {
        return new self(new ReflectionParameter([$ip[0], $ip[1]], $ip[2]));
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
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
    #[Override]
    public function getClass(): ReflectionClass
    {
        $class = $this->parameter->getDeclaringClass();
        assert($class instanceof \ReflectionClass);

        return new ReflectionClass($class->getName());
    }

    /**
     * {@inheritDoc}
     *
     * @return IpParameters
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    #[Override]
    public function getQualifiers(): array
    {
        return [$this->getQualifier()];
    }

    /**
     * {@inheritDoc}
     *
     * @return object|null
     *
     * @throws ReflectionException
     */
    public function getQualifier()
    {
        // Try method attributes first
        $parameter = $this->getParameter();
        $class = $parameter->getDeclaringClass();
        $methodName = $parameter->getDeclaringFunction()->getShortName();
        assert($class instanceof \ReflectionClass);

        $nativeMethod = new \ReflectionMethod($class->getName(), $methodName);
        $methodAttributes = $nativeMethod->getAttributes();

        foreach ($methodAttributes as $attribute) {
            if ($this->isQualifier($attribute)) {
                return $attribute->newInstance();
            }
        }

        // Try parameter attributes
        $paramAttributes = $parameter->getAttributes();

        foreach ($paramAttributes as $attribute) {
            if ($this->isQualifier($attribute)) {
                return $attribute->newInstance();
            }
        }

        return null;
    }

    /**
     * @phpstan-param ReflectionAttribute<object> $attribute
     *
     * @throws ReflectionException
     *
     * @psalm-suppress TooManyTemplateParams
     */
    private function isQualifier(ReflectionAttribute $attribute): bool
    {
        $attributeClass = $attribute->getName();
        $reflectionClass = new \ReflectionClass($attributeClass);
        $classAttributes = $reflectionClass->getAttributes(Qualifier::class);

        return count($classAttributes) > 0;
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Attribute;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\Qualifier;
use ReflectionParameter;

class InjectionPointTest extends TestCase
{
    /** @var InjectionPoint  */
    private $injectionPoint;

    /** @var ReflectionParameter */
    private $parameter;

    protected function setUp(): void
    {
        $reflectionClass = new \ReflectionClass(FooClass::class);
        $reflectionMethod = $reflectionClass->getMethod('testMethod');
        $this->parameter = $reflectionMethod->getParameters()[0];
        $this->injectionPoint = InjectionPoint::getInstance([FooClass::class, 'testMethod', 'param']);
    }

    public function testGetParameter(): void
    {
        $actual = $this->injectionPoint->getParameter();
        $this->assertInstanceOf(ReflectionParameter::class, $actual);
        $this->assertEquals($this->parameter, $actual);
    }

    public function testGetMethod(): void
    {
        $method = $this->injectionPoint->getMethod();
        $this->assertInstanceOf(ReflectionMethod::class, $method);
        $this->assertSame('testMethod', $method->getName());
    }

    public function testGetClass(): void
    {
        $class = $this->injectionPoint->getClass();
        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame(FooClass::class, $class->getName());
    }

    public function testGetQualifiers(): void
    {
        $qualifiers = $this->injectionPoint->getQualifiers();
        $this->assertIsArray($qualifiers); // @phpstan-ignore-line
        $this->assertCount(1, $qualifiers);
    }

    public function testGetQualifierReturnsNullWhenNoQualifier(): void
    {
        $qualifier = $this->injectionPoint->getQualifier();
        $this->assertNull($qualifier);
    }
}

class FooClass
{
    public function testMethod(string $param): void
    {
    }
}

#[Attribute]
#[Qualifier]
class TestQualifier
{
}

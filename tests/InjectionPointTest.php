<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use ReflectionParameter;

class InjectionPointTest extends TestCase
{
    private InjectionPoint $injectionPoint;
    private ReflectionParameter $parameter;

    protected function setUp(): void
    {
        $reflectionClass = new \ReflectionClass(FakeTestClass::class);
        $reflectionMethod = $reflectionClass->getMethod('testMethod');
        $this->parameter = $reflectionMethod->getParameters()[0];
        $this->injectionPoint = InjectionPoint::getInstance([FakeTestClass::class, 'testMethod', 'param']);
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
        $this->assertSame(FakeTestClass::class, $class->getName());
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

    public function testGetQualifierReturnsMethodLevelQualifier(): void
    {
        // Test with FakeLoggerConsumer which has #[FakeLoggerInject] on setLogger method
        $injectionPoint = InjectionPoint::getInstance([FakeLoggerConsumer::class, 'setLogger', 'logger']);
        $qualifier = $injectionPoint->getQualifier();

        $this->assertInstanceOf(FakeLoggerInject::class, $qualifier);
        $this->assertSame('MEMORY', $qualifier->type);
    }

    public function testGetQualifierReturnsParameterLevelQualifier(): void
    {
        // Test with FakeParameterQualifierConsumer which has #[FakeParameterQualifier] on parameter
        $injectionPoint = InjectionPoint::getInstance([FakeParameterQualifierConsumer::class, 'setRobot', 'robot']);
        $qualifier = $injectionPoint->getQualifier();

        $this->assertInstanceOf(FakeParameterQualifier::class, $qualifier);
        $this->assertSame('special', $qualifier->value);
    }
}

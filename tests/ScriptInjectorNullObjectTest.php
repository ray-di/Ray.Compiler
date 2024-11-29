<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

use function passthru;
use function sprintf;

class ScriptInjectorNullObjectTest extends TestCase
{
    protected function setUp(): void
    {
        deleteFiles(__DIR__ . '/tmp');
    }

    public function testNullObjectCompile(): CompileInjector
    {
        passthru(sprintf('php %s/script/null_object.php', __DIR__));

        $injector = new CompileInjector(
            __DIR__ . '/tmp/null_object',
            new LazyModule(new FakeNullObjectModule())
        );
        $instance = $injector->getInstance(FakeTyreInterface::class);
        $this->assertInstanceOf(FakeTyreInterface::class, $instance);

        return $injector;
    }
}

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

    public function testNullObjectCompile(): CompiledInjector
    {
        passthru(sprintf('php %s/script/null_object.php', __DIR__));

        $scriptDir = __DIR__ . '/tmp/null_object';
        (new Compiler())->compile(new FakeNullObjectModule(), $scriptDir);
        $injector = new CompiledInjector($scriptDir);
        $instance = $injector->getInstance(FakeTyreInterface::class);
        $this->assertInstanceOf(FakeTyreInterface::class, $instance);

        return $injector;
    }
}

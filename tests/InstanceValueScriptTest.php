<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

use function is_dir;
use function mkdir;
use function sprintf;

/** An instance-bound value is arbitrary user input; it must not reach the generated code raw. */
class InstanceValueScriptTest extends TestCase
{
    /**
     * The serialize() output went into a quoted literal unescaped, so an apostrophe anywhere in
     * the bound value closed it and the compiled script stopped being PHP.
     */
    public function testQuoteInAnInstanceValueCannotEscapeTheGeneratedLiteral(): void
    {
        $scriptDir = __DIR__ . '/tmp/instance-value';
        deleteFiles($scriptDir);
        if (! is_dir($scriptDir) && ! mkdir($scriptDir, 0777, true)) {
            self::fail(sprintf('Could not create %s', $scriptDir));
        }

        (new Compiler())->compile(new FakeInstanceValueModule(), $scriptDir);

        $instance = (new CompiledInjector($scriptDir))->getInstance(FakeInstanceValueConsumer::class);

        $this->assertInstanceOf(FakeInstanceValueConsumer::class, $instance);
        $this->assertSame(FakeInstanceValueModule::PAYLOAD, $instance->value);
    }
}

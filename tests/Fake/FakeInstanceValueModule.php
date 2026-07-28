<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeInstanceValueModule extends AbstractModule
{
    /** Both characters that can break out of the literal holding the serialize() output. */
    public const PAYLOAD = ["it's" => 'C:\\path\\'];

    protected function configure()
    {
        $this->bind('')->annotatedWith('payload')->toInstance(self::PAYLOAD);
        $this->bind(FakeInstanceValueConsumer::class);
    }
}

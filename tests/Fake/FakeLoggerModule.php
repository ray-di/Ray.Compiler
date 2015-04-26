<?php

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeLoggerModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeLoggerInterface::class)->toProvider(FakeLoggerPointProvider::class);
        $this->bind(FakeLoggerConsumer::class);
    }
}

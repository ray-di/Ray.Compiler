<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeToConstructorQualifierModule extends AbstractModule
{
    public function __construct(
        private string $qualifier
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->bind()->annotatedWith($this->qualifier)->toInstance('qualified');
        $this->bind(FakeQualifierConsumerInterface::class)->toConstructor(FakeQualifiedValueConsumer::class, ['value' => $this->qualifier]);
    }
}

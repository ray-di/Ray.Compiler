<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;

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
        $this->bind(FakeEngineInterface::class)->annotatedWith($this->qualifier)->to(FakeEngine::class)->in(Scope::SINGLETON);
        $this->bind(FakeQualifierConsumerInterface::class)->toConstructor(
            FakeQualifiedValueConsumer::class,
            ['value' => $this->qualifier, 'engine' => $this->qualifier],
        );
    }
}

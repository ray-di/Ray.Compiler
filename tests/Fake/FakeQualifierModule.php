<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class FakeQualifierModule extends AbstractModule
{
    /** @param list<string> $qualifiers */
    public function __construct(
        private array $qualifiers,
        private string|null $consumer = null,
        private bool $singleton = false
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        foreach ($this->qualifiers as $qualifier) {
            $bind = $this->bind(FakeEngineInterface::class)->annotatedWith($qualifier)->to(FakeEngine::class);
            if ($this->singleton) {
                $bind->in(Scope::SINGLETON);
            }
        }

        if ($this->consumer === null) {
            return;
        }

        $this->bind(FakeQualifierConsumerInterface::class)->to($this->consumer);
    }
}

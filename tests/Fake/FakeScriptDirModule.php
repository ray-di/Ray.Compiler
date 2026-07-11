<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeScriptDirModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(FakeScriptDirConsumerInterface::class)->annotatedWith('ctor')->to(FakeScriptDirConstructorConsumer::class);
        $this->bind(FakeScriptDirConsumerInterface::class)->annotatedWith('setter')->to(FakeScriptDirSetterConsumer::class);
        $this->bind(FakeScriptDirConsumerInterface::class)->annotatedWith('provider')->toProvider(FakeScriptDirProvider::class);
        $this->bind(FakeScriptDirConsumerInterface::class)->annotatedWith('aop')->to(FakeScriptDirAopConsumer::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(FakeScriptDirAopConsumer::class),
            $this->matcher->any(),
            [FakeInterceptor::class],
        );
    }
}

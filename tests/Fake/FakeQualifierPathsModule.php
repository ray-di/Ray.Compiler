<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class FakeQualifierPathsModule extends AbstractModule
{
    protected function configure()
    {
        // annotatedWith(string) reached through #[Named] on a constructor parameter
        $this->bind(FakeEngineInterface::class)->annotatedWith('ctor.path')->to(FakeEngine::class);
        // annotatedWith(class-string) reached through a custom #[Qualifier] attribute
        $this->bind(FakeEngineInterface::class)->annotatedWith(FakePathQualifier::class)->to(FakeEngine::class);
        // annotatedWith(string) on a provider binding
        $this->bind(FakeEngineInterface::class)->annotatedWith('prov.path')->toProvider(FakeQualifierPathsProvider::class);
        // annotatedWith(string) reached through setter injection, as a singleton
        $this->bind(FakeEngineInterface::class)->annotatedWith('setter.path')->to(FakeEngine::class)->in(Scope::SINGLETON);
        // annotatedWith(string) on an instance binding; the value itself may hold anything
        $this->bind('')->annotatedWith('inst.path')->toInstance('value/with/slash');

        $this->bind(FakeQualifierConsumerInterface::class)->to(FakeQualifierPathsRoot::class);
    }
}

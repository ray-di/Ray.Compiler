<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake\MultiBindings;

use Ray\Compiler\FakeDoubleInterceptor;
use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeNamedEngine;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeRobotProvider;
use Ray\Di\AbstractModule;
use Ray\Di\MultiBinder;

/** Multibinding targets are not bound, as the Ray.Di manual allows. */
final class FakeUnboundTargetMultiBindingsModule extends AbstractModule
{
    public function __construct(
        private bool $bindTargetDependency = true
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $engines = MultiBinder::newInstance($this, FakeEngineInterface::class);
        $engines->addBinding('one')->to(FakeEngine::class);
        $engines->addBinding('named')->to(FakeNamedEngine::class);
        MultiBinder::newInstance($this, FakeRobotInterface::class)->addBinding('provider')->toProvider(FakeRobotProvider::class);
        $this->bind(FakeMultiBindingConsumer::class);
        $this->bindInterceptor($this->matcher->subclassesOf(FakeNamedEngine::class), $this->matcher->any(), [FakeDoubleInterceptor::class]);
        if ($this->bindTargetDependency) {
            $this->bind()->annotatedWith(FakeNamedEngine::NAME)->toInstance('qiq');
        }
    }
}

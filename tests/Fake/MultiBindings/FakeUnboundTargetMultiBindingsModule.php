<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake\MultiBindings;

use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeRobotProvider;
use Ray\Di\AbstractModule;
use Ray\Di\MultiBinder;

/** Multibinding targets are not bound, as the Ray.Di manual allows. */
final class FakeUnboundTargetMultiBindingsModule extends AbstractModule
{
    protected function configure(): void
    {
        MultiBinder::newInstance($this, FakeEngineInterface::class)->addBinding('one')->to(FakeEngine::class);
        MultiBinder::newInstance($this, FakeRobotInterface::class)->addBinding('provider')->toProvider(FakeRobotProvider::class);
        $this->bind(FakeMultiBindingConsumer::class);
    }
}

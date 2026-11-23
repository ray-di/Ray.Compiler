<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake\MultiBindings;

use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeRobotProvider;
use Ray\Di\AbstractModule;
use Ray\Di\MultiBinder;

/** Adds a multibinding target without binding it */
final class FakeUnboundTargetMultiBindingsModule extends AbstractModule
{
    public function __construct(
        private bool $provider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        if ($this->provider) {
            MultiBinder::newInstance($this, FakeRobotInterface::class)->addBinding('provider')->toProvider(FakeRobotProvider::class);

            return;
        }

        MultiBinder::newInstance($this, FakeEngineInterface::class)->addBinding('one')->to(FakeEngine::class);
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake\MultiBindings;


use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngine2;
use Ray\Compiler\MultiBindings\FakeEngine3;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeMultiBindingAnnotation;
use Ray\Compiler\MultiBindings\FakeMultiBindingConsumer;
use Ray\Compiler\MultiBindings\FakeRobot;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeRobotProvider;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithMap;
use Ray\Compiler\MultiBindings\FakeSetNotFoundWithProvider;
use Ray\Di\AbstractModule;
use Ray\Di\MultiBinder;

final class FakeMultiBindingsModule extends AbstractModule
{
    protected function configure(): void
    {
        $engineBinder = MultiBinder::newInstance($this, FakeEngineInterface::class);
        $engineBinder->addBinding('one')->to(FakeEngine::class);
        $engineBinder->addBinding('two')->to(FakeEngine2::class);
        $engineBinder->addBinding()->to(FakeEngine3::class);
        $robotBinder = MultiBinder::newInstance($this, FakeRobotInterface::class);
        $robotBinder->addBinding('to')->to(FakeRobot::class);
        $robotBinder->addBinding('provider')->toProvider(FakeRobotProvider::class);
        $robotBinder->addBinding('instance')->toInstance(new FakeRobot());
        $this->bind(FakeMultiBindingAnnotation::class);
        $this->bind(FakeMultiBindingConsumer::class);

        $this->bind(FakeEngine::class);
        $this->bind(FakeEngine2::class);
        $this->bind(FakeEngine3::class);
        $this->bind(FakeRobot::class);
        $this->bind(FakeRobotProvider::class);

        $this->bind(FakeSetNotFoundWithMap::class);
        $this->bind(FakeSetNotFoundWithProvider::class);
    }
}

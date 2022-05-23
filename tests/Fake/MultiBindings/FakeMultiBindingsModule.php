<?php

declare(strict_types=1);

namespace Ray\Compiler\Fake\MultiBindings;


use Ray\Compiler\MultiBindings\FakeEngine;
use Ray\Compiler\MultiBindings\FakeEngine2;
use Ray\Compiler\MultiBindings\FakeEngine3;
use Ray\Compiler\MultiBindings\FakeEngineInterface;
use Ray\Compiler\MultiBindings\FakeRobot;
use Ray\Compiler\MultiBindings\FakeRobotInterface;
use Ray\Compiler\MultiBindings\FakeRobotProvider;
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
    }
}

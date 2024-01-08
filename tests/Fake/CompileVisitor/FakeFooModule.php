<?php

declare(strict_types=1);

namespace Ray\Compiler\CompileVisitor;

use Ray\Compiler\FakeCar;
use Ray\Compiler\FakeCarInterface;
use Ray\Compiler\FakeEngine;
use Ray\Compiler\FakeEngineInterface;
use Ray\Compiler\FakeHandleInterface;
use Ray\Compiler\FakeHandleProvider;
use Ray\Compiler\FakeHardtop;
use Ray\Compiler\FakeHardtopInterface;
use Ray\Compiler\FakeInterceptor;
use Ray\Compiler\FakeMirrorInterface;
use Ray\Compiler\FakeMirrorLeft;
use Ray\Compiler\FakeMirrorRight;
use Ray\Compiler\FakeRobot;
use Ray\Compiler\FakeTyre;
use Ray\Compiler\FakeTyreInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class FakeFooModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(FakeFooInterface::class)->to(FakeFoo::class);
    }
}

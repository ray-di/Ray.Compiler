<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Aop\NullInterceptor;
use Ray\Di\AbstractModule;

class FakeNullBindingModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeAopInterface::class)->toNull();
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->any(),
            [NullInterceptor::class]
        );
    }
}

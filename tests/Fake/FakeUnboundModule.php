<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Fake\MultiBindings\FakeMultiBindingsModule;
use Ray\Di\AbstractModule;

class FakeUnboundModule implements LazyModuleInterface
{
    public function __invoke(): AbstractModule
    {
        $module = new class extends AbstractModule
        {
            protected function configure()
            {
                $this->bind(FakeCar3::class);
            }
        };

        return $module;
    }
}

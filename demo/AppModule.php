<?php

declare(strict_types=1);

namespace Ray\Compiler\Demo;

use Ray\Di\AbstractModule;

final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(GreeterInterface::class)->to(Greeter::class);
    }
}

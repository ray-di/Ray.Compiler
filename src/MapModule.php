<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\MultiBinding\Map;
use Ray\Di\MultiBinding\MapProvider;
use Ray\Di\MultiBinding\MultiBindings;

class MapModule extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->bind(MultiBindings::class);
        $this->bind(Map::class)->toProvider(MapProvider::class);
    }
}

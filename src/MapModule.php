<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\MultiBinding\Map;
use Ray\Di\MultiBinding\MapProvider;

class MapModule extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->bind(Map::class)->toProvider(MapProvider::class);
    }
}

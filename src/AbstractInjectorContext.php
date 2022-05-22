<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Ray\Di\AbstractModule;

abstract class AbstractInjectorContext
{
    /**
     * @var string
     * @readonly
     */
    public $tmpDir;

    public function __construct(string $tmpdDir)
    {
        $this->tmpDir = $tmpdDir;
    }

    abstract public function getModule(): AbstractModule;

    abstract public function getCache(): CacheProvider;

    /**
     * @return array<class-string>
     */
    protected function getCachedInstance(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Override;
use Ray\Di\AbstractModule;

final class OverrideLazyModule implements LazyModuleInterface
{
    /** @var callable(): AbstractModule */
    private $modules;

    /** @param callable(): AbstractModule $modules */
    public function __construct(callable $modules, private readonly AbstractModule $overrideModule)
    {
        $this->modules = $modules;
    }

    #[Override]
    public function __invoke(): AbstractModule
    {
        $module = ($this->modules)();
        $module->override($this->overrideModule);

        return $module;
    }
}

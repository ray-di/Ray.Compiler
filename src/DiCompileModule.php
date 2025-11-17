<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Override;
use Ray\Compiler\Annotation\Compile;
use Ray\Di\AbstractModule;

final class DiCompileModule extends AbstractModule
{
    public function __construct(private readonly bool $doCompile, ?AbstractModule $module = null)
    {
        parent::__construct($module);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        $this->bind()->annotatedWith(Compile::class)->toInstance($this->doCompile);
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\ProviderInterface;
use Ray\Di\SetContextInterface;

class FakeTypedPropertyContextualProvider implements ProviderInterface, SetContextInterface
{
    private string $context;

    /**
     * @inheritDoc
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function get()
    {
        return new FakeContextualRobot($this->context);
    }
}

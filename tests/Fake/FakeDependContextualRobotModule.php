<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeDependContextualRobotModule extends AbstractModule
{

    /** @var string */
    private $context;

    public function __construct(string $context, ?AbstractModule $module = null)
    {
        $this->context = $context;
        parent::__construct($module);
    }

    protected function configure()
    {
        $this->bind(FakeRobotInterface::class)->toProvider(FakeTypedPropertyContextualProvider::class, $this->context);
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

/** Every way Ray.Di can deliver a qualifier, gathered into one object. */
class FakeQualifierPathsRoot implements FakeQualifierConsumerInterface
{
    public $engine;
    public $qualifierClass;
    public $provided;
    public $instance;
    public $setter;

    public function __construct(
        #[Named('ctor/slash')]
        FakeEngineInterface $engine,
        #[FakePathQualifier]
        FakeEngineInterface $qualifierClass,
        #[Named('prov/slash')]
        FakeEngineInterface $provided,
        #[Named('inst/slash')]
        string $instance
    ) {
        $this->engine = $engine;
        $this->qualifierClass = $qualifierClass;
        $this->provided = $provided;
        $this->instance = $instance;
    }

    #[Inject]
    public function setEngine(#[Named('setter/slash')] FakeEngineInterface $engine): void
    {
        $this->setter = $engine;
    }
}

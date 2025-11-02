<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use Ray\Di\Di\PostConstruct;

class FakeCar implements FakeCarInterface
{
    public $engine;
    public $hardtop;
    public $frontTyre;
    public $rearTyre;
    public $isConstructed = false;
    public $rightMirror;
    public $leftMirror;
    public $spareMirror;
    public $oil;

    /** @var FakeHandleInterface */
    public $handle;
    public $null = false;

    #[Inject]
    public function setTires(FakeTyreInterface $frontTyre, FakeTyreInterface $rearTyre, $null = null)
    {
        $this->frontTyre = $frontTyre;
        $this->rearTyre = $rearTyre;
        $this->null = $null;
    }

    #[Inject(optional: true)]
    public function setHardtop(FakeHardtopInterface $hardtop)
    {
        $this->hardtop = $hardtop;
    }

    #[Inject]
    public function setMirrors(
        #[Named('right')] FakeMirrorInterface $rightMirror,
        #[Named('left')] FakeMirrorInterface $leftMirror
    ) {
        $this->rightMirror = $rightMirror;
        $this->leftMirror = $leftMirror;
    }

    #[Inject]
    public function setSpareMirror(#[Named('right')] FakeMirrorInterface $rightMirror)
    {
        $this->spareMirror = $rightMirror;
    }

    #[Inject]
    public function setHandle(FakeHandleInterface $handle)
    {
        $this->handle = $handle;
    }

    #[Inject]
    public function setOil(FakeOilInterface $oil)
    {
        $this->oil = $oil;
    }

    /**
     * Inject annotation at constructor is just for human, not mandatory.
     */
    public function __construct(FakeEngineInterface $engine)
    {
        $this->engine = $engine;
    }


    #[PostConstruct]
    public function postConstruct()
    {
        $isEngineInstalled = $this->engine instanceof FakeEngine;
        $isTyreInstalled = $this->frontTyre instanceof FakeTyre;
        if ($isEngineInstalled && $isTyreInstalled) {
            $this->isConstructed = true;
        }
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler\MultiBindings;


use Ray\Di\Di\Set;
use Ray\Di\MultiBinding\Map;
use Ray\Di\ProviderInterface;

final class FakeSetNotFoundWithProvider
{
    /**
     * This property should Set annotated for setProviderButNotSetFound method.
     * SetNotFound exception will be thrown.
     */
    public $engineProvider;

    public function __construct(
        ProviderInterface $engineProvider
    ){
        $this->$engineProvider = $engineProvider;
    }
}

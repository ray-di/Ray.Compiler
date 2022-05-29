<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;


final class FakeDeep
{
    public $dep;
    public function __construct(
        FakeDep $dep
    )
    {
        $this->dep = $dep;
    }
}

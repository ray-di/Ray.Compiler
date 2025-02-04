<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeOil implements FakeOilInterface
{
    public $brand;

    public function __construct(string $brand = 'mobil')
    {
        $this->brand = $brand;
    }
}

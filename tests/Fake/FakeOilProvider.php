<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\InjectionPointInterface;
use Ray\Di\ProviderInterface;
use function get_class;

class FakeOilProvider implements ProviderInterface
{
    private $ip;

    public function __construct(InjectionPointInterface $ip)
    {
        $this->ip = $ip;
    }

    public function get(): FakeOil
    {
        return new FakeOil((string)($this->ip->getClass()));
    }

}

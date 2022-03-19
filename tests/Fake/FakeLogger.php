<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\InjectionPointInterface;

class FakeLogger implements FakeLoggerInterface
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var InjectionPointInterface */
    public $ip;

    public function __construct($name, $type, InjectionPointInterface $ip)
    {
        $this->name = $name;
        $this->type = $type;
        $this->ip = $ip;
    }
}

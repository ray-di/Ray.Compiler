<?php

namespace Ray\Compiler\CompileVisitor;

use Ray\Di\InjectionPointInterface;
use Ray\Di\ProviderInterface;

class FakeBazProvider implements ProviderInterface
{
    /**
     * @var InjectionPointInterface
     */
    private $ip;

    public function __construct(InjectionPointInterface $ip)
    {
        // TODO: Implement get() method.
        $this->ip = $ip;
    }

    public function get(): FakeBaz
    {
        $qualifers = $this->ip->getQualifiers();

        return new FakeBaz($qualifers);
    }

}

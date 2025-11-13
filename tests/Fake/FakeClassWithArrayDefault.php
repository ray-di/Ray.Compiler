<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeClassWithArrayDefault
{
    /** @var array<string, mixed> */
    public $config;

    /** @var array<int> */
    public $options;

    /**
     * @param array<string, mixed> $config
     * @param array<int> $options
     */
    public function __construct(array $config = ['key' => 'value'], array $options = [1, 2, 3])
    {
        $this->config = $config;
        $this->options = $options;
    }
}
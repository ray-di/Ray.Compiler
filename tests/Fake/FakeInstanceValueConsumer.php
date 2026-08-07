<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Named;

class FakeInstanceValueConsumer
{
    /** An instance-bound array reaches the script as serialize() output. */
    public $value;

    /** @param array<mixed> $value */
    public function __construct(
        #[Named('payload')]
        array $value
    ) {
        $this->value = $value;
    }
}

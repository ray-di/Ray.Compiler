<?php

declare(strict_types=1);

namespace Ray\Compiler;

class FakeQualifiedValueConsumer implements FakeQualifierConsumerInterface
{
    public function __construct(
        public string $value
    ) {
    }
}

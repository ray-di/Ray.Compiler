<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Named;

/** A qualifier ending in a backslash: passes name validation, but escapes the closing quote of a raw literal. */
class FakeBackslashQualifierConsumer implements FakeQualifierConsumerInterface
{
    public const QUALIFIER = 'q\\';

    public $engine;

    public function __construct(
        #[Named(self::QUALIFIER)]
        FakeEngineInterface $engine
    ) {
        $this->engine = $engine;
    }
}

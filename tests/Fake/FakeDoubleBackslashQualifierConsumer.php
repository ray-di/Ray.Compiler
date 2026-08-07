<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Named;

/** Consecutive backslashes evaluate differently inside a raw single-quoted literal. */
class FakeDoubleBackslashQualifierConsumer implements FakeQualifierConsumerInterface
{
    public const QUALIFIER = 'a\\\\b';

    public $engine;

    public function __construct(
        #[Named(self::QUALIFIER)]
        FakeEngineInterface $engine
    ) {
        $this->engine = $engine;
    }
}

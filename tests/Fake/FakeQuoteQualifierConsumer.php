<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Named;

/**
 * A qualifier holding both characters that can break out of the index literal.
 *
 * Interpolated raw, the quote closes the literal and what follows is compiled as PHP;
 * the trailing backslash escapes the closing quote and the script stops parsing.
 */
class FakeQuoteQualifierConsumer implements FakeQualifierConsumerInterface
{
    public const QUALIFIER = "q'.PHP_EOL.'\\";

    public $engine;

    public function __construct(
        #[Named("q'.PHP_EOL.'\\")]
        FakeEngineInterface $engine
    ) {
        $this->engine = $engine;
    }
}

<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\Di\Named;

/** A qualifier that reads like a path: the slash must not become a directory. */
class FakeSlashQualifierConsumer implements FakeQualifierConsumerInterface
{
    public $engine;

    public function __construct(
        #[Named('a/b')]
        FakeEngineInterface $engine
    ) {
        $this->engine = $engine;
    }
}

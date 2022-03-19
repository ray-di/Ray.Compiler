<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

class FilePutContentsTest extends TestCase
{
    public function testInvoke(): void
    {
        (new FilePutContents())(__DIR__ . '/tmp/a.txt', 'a');
        $this->assertFileExists(__DIR__ . '/tmp/a.txt');
    }
}

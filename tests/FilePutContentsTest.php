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

    public function testInvokeWithNestedDirectory(): void
    {
        $testFile = __DIR__ . '/tmp/nested/dir/test.txt';
        (new FilePutContents())($testFile, 'nested content');
        $this->assertFileExists($testFile);
        $this->assertSame('nested content', file_get_contents($testFile));
    }
}

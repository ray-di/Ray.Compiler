<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Compiler\Exception\FileNotWritable;

final class FilePutContents
{
    public function __invoke(string $filename, string $content) : void
    {
        $tmpFile = tempnam(dirname($filename), 'swap');
        if (is_string($tmpFile) && file_put_contents($tmpFile, $content) && @rename($tmpFile, $filename)) {
            return;
        }
        @unlink((string) $tmpFile);

        throw new FileNotWritable($filename);
    }
}

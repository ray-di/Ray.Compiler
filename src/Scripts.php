<?php

declare(strict_types=1);

namespace Ray\Compiler;

use function sprintf;
use function str_replace;

final class Scripts
{
    /** @var array<string, string> */
    private $scripts = [];

    public function add(string $index, string $script): void
    {
        $this->scripts[$index] = $script;
    }

    public function save(string $scriptDir): void
    {
        $template = <<<'EOL'
<?php
%s
EOL;
        $filePutContents = new FilePutContents();
        foreach ($this->scripts as $index => $script) {
            $file = sprintf('%s/%s.php', $scriptDir, str_replace('\\', '_', $index));
            $script = sprintf($template, $script);
            $filePutContents($file, $script);
        }
    }
}

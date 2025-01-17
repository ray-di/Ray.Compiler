<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

use function array_map;
use function file_get_contents;
use function glob;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;

/** @covers \Ray\Compiler\Scripts */
class ScriptsTest extends TestCase
{
    public function testAdd(): void
    {
        $scripts = new Scripts();

        $scripts->add('Test\Index', 'Test content');
        $this->assertEquals(1, $scripts->count());
    }

    public function testCount(): void
    {
        $scripts = new Scripts();

        $this->assertEquals(0, $scripts->count());

        $scripts->add('First\Script', 'Content of first script');
        $scripts->add('Second\Script', 'Content of second script');

        $this->assertEquals(2, $scripts->count());
    }

    public function testSave(): void
    {
        $scripts = new Scripts();
        $tempDir = sys_get_temp_dir() . '/scripts_test';

        if (! is_dir($tempDir)) {
            mkdir($tempDir);
        }

        $scripts->add('Test\FirstScript', 'echo "First Script";');
        $scripts->add('Test\SecondScript', 'echo "Second Script";');

        $scripts->save($tempDir);

        $this->assertFileExists($tempDir . '/Test_FirstScript.php');
        $this->assertFileExists($tempDir . '/Test_SecondScript.php');

        $firstScriptContent = (string) file_get_contents($tempDir . '/Test_FirstScript.php');
        $secondScriptContent = (string) file_get_contents($tempDir . '/Test_SecondScript.php');

        $this->assertStringContainsString('<?php', $firstScriptContent);
        $this->assertStringContainsString('echo "First Script";', $firstScriptContent);

        $this->assertStringContainsString('<?php', $secondScriptContent);
        $this->assertStringContainsString('echo "Second Script";', $secondScriptContent);

        // Clean up
        array_map('unlink', (array) glob($tempDir . '/*.php')); // @phpstan-ignore-line
        rmdir($tempDir);
    }
}

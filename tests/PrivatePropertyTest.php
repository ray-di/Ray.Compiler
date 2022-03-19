<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;

class PrivatePropertyTest extends TestCase
{
    public function testDefault(): void
    {
        $prop = (new PrivateProperty())('_invalid_', '_invalid_', 'default');
        $this->assertSame('default', $prop);
    }
}

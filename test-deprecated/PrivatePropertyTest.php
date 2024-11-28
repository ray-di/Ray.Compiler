<?php

declare(strict_types=1);

namespace Ray\test-deprecated;

use PHPUnit\Framework\TestCase;
use stdClass;

class PrivatePropertyTest extends TestCase
{
    public function testDefault(): void
    {
        $prop = (new PrivateProperty())(new stdClass(), '_invalid_', 'default');
        $this->assertSame('default', $prop);
    }
}

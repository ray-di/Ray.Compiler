<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use function spl_object_hash;

class CachedFactoryTest extends TestCase
{
    public function testInsntanceCached() : void
    {
        $cache = new ArrayCache;
        $injector1 = CachedFactory::getInstance(
            FakeToBindPrototypeModule::class,
            [],
            __DIR__ . '/tmp/base',
            $cache
        );
        $injector2 = CachedFactory::getInstance(
            FakeToBindPrototypeModule::class,
            [],
            __DIR__ . '/tmp/base',
            $cache
        );
        $this->assertSame(spl_object_hash($injector1), spl_object_hash($injector2));
    }
}

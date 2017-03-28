<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\Node\Scalar\String_;

class NormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testString()
    {
        $normalizer = new Normalizer;
        $string = $normalizer('ray');
        /* @var $string String_ */
        $this->assertInstanceOf(String_::class, $string);
        $this->assertSame('ray', $string->value);
    }

    /**
     * @expectedException \Ray\Compiler\Exception\InvalidInstance
     */
    public function testInvalidValue()
    {
        $normalizer = new Normalizer;
        $resource = \fopen(__FILE__, 'r');
        $normalizer($resource);
    }
}

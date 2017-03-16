<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class Code
{
    /**
     * @var bool
     */
    public $isSingleton;

    /**
     * @var array
     */
    public $qualifiers;

    /**
     * @var Node
     */
    private $node;

    public function __construct(Node $node, $isSingleton = false, IpQualifier $qualifier = null)
    {
        $this->node = $node;
        $this->isSingleton = $isSingleton;
        $this->qualifiers = $qualifier;
    }

    public function __toString()
    {
        $prettyPrinter = new Standard();
        $classCode = $prettyPrinter->prettyPrintFile([$this->node]);

        return $classCode;
    }
}

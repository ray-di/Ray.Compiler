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
     * @var Node
     */
    private $node;

    /**
     * @param Node $node
     * @param bool $isSingleton
     */
    public function __construct(Node $node, $isSingleton = false)
    {
        $this->node = $node;
        $this->isSingleton = $isSingleton;
    }

    public function __toString()
    {
        $prettyPrinter = new Standard();
        $classCode = $prettyPrinter->prettyPrintFile([$this->node]);

        return $classCode;
    }
}

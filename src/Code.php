<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class Code
{
    /**
     * @var Node
     */
    private $node;

    /**
     * @var bool
     */
    public $isSingleton;

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

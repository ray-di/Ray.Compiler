<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;

final class Code
{
    /**
     * @var Node
     */
    private $node;

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public function __toString()
    {
        $prettyPrinter = new \PhpParser\PrettyPrinter\Standard();
        $classCode = $prettyPrinter->prettyPrintFile([$this->node]);

        return $classCode;
    }
}

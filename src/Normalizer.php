<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use Ray\Compiler\Exception\InvalidInstance;
use Ray\Di\InjectorInterface;

/**
 * Value to code(Node) converter
 */
final class Normalizer
{
    /**
     * Normalizes a value: Converts nulls, booleans, integers,
     * floats, strings and arrays into their respective nodes
     *
     * @param mixed $value The value to normalize
     *
     * @return Expr The normalized value
     */
    public function __invoke($value)
    {
        if ($value === null) {
            return new Expr\ConstFetch(
                new Node\Name('null')
            );
        } elseif (\is_bool($value)) {
            return new Expr\ConstFetch(
                new Node\Name($value ? 'true' : 'false')
            );
        } elseif (\is_int($value)) {
            return new Scalar\LNumber($value);
        } elseif (\is_float($value)) {
            return new Scalar\DNumber($value);
        } elseif (\is_string($value)) {
            return new Scalar\String_($value);
        }

        return $this->noScalar($value);
    }

    /**
     * Return array or object node
     *
     * @param mixed $value
     *
     * @return Expr\Array_|Expr\FuncCall
     */
    private function noScalar($value)
    {
        if (\is_array($value)) {
            return $this->arrayValue($value);
        } elseif (\is_object($value)) {
            return $this->normalizeObject($value);
        }
        throw new InvalidInstance; //@codeCoverageIgnore
    }

    /**
     * Return "unserialize($object)" node
     *
     * @param object $object
     *
     * @return Expr\FuncCall
     */
    private function normalizeObject($object)
    {
        if ($object instanceof InjectorInterface) {
            return new Expr\FuncCall(new Expr\Variable('injector'));
        }
        $serialize = new Scalar\String_(\serialize($object));

        return new Expr\FuncCall(new Node\Name('unserialize'), [new Arg($serialize)]);
    }

    /**
     * Return array value node
     *
     * @param $value
     *
     * @return Expr\Array_
     */
    private function arrayValue($value)
    {
        $items = [];
        $lastKey = -1;
        foreach ($value as $itemKey => $itemValue) {
            // for consecutive, numeric keys don't generate keys
            if (null !== $lastKey && ++$lastKey === $itemKey) {
                $items[] = new Expr\ArrayItem(
                    $this->__invoke($itemValue)
                );
            } else {
                $lastKey = null;
                $items[] = new Expr\ArrayItem(
                    $this->__invoke($itemValue),
                    $this->__invoke($itemKey)
                );
            }
        }

        return new Expr\Array_($items);
    }
}

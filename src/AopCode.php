<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 */
namespace Ray\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use Ray\Aop\Interceptor;
use Ray\Di\Dependency;
use Ray\Di\Name;

final class AopCode
{
    /**
     * @var PrivateProperty
     */
    private $privateProperty;

    public function __construct(PrivateProperty $privateProperty)
    {
        $this->privateProperty = $privateProperty;
    }

    /**
     * Add aop factory code if bindings are given
     *
     * @param Dependency $dependency
     * @param array      $node
     */
    public function __invoke(Dependency $dependency, array &$node)
    {
        $prop = $this->privateProperty;
        $newInstance = $prop($dependency, 'newInstance');
        $bind = $prop($newInstance, 'bind');
        $bind = $prop($bind, 'bind');
        /** @var array $bindings */
        $bindings = $prop($bind, 'bindings', null);
        if (! $bindings || ! is_array($bindings)) {
            return;
        }
        $methodBinding = $this->getMethodBinding($bindings);
        $bindingsProp = new Expr\PropertyFetch(new Expr\Variable('instance'), 'bindings');
        $node[] = new Expr\Assign($bindingsProp, new Expr\Array_($methodBinding));
    }

    /**
     * @param Interceptor[] $bindings
     *
     * @return Expr\ArrayItem[]
     */
    private function getMethodBinding($bindings)
    {
        $methodBinding = [];
        foreach ($bindings as $method => $interceptors) {
            $items = [];
            foreach ($interceptors as $interceptor) {
                // $singleton('FakeAopInterface-*');
                $dependencyIndex = "{$interceptor}-" . Name::ANY;
                $singleton = new Expr\FuncCall(new Expr\Variable('singleton'), [new Node\Arg(new Scalar\String_($dependencyIndex))]);
                // [$singleton('FakeAopInterface-*'), $singleton('FakeAopInterface-*');]
                $items[] = new Expr\ArrayItem($singleton);
            }
            $arr = new Expr\Array_($items);
            $methodBinding[] = new Expr\ArrayItem($arr, new Scalar\String_($method));
        }

        return $methodBinding;
    }
}

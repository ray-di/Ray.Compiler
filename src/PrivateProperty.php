<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 *
 * taken from BuilderAbstract::PhpParser() and modified for object
 */
namespace Ray\Compiler;

final class PrivateProperty
{
    /**
     * @param object $object
     * @param string $prop
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function __invoke($object, $prop, $default = null)
    {
        try {
            $refProp = (new \ReflectionProperty($object, $prop));
        } catch (\Exception $e) {
            return $default;
        }
        $refProp->setAccessible(true);
        $value = $refProp->getValue($object);

        return $value;
    }
}

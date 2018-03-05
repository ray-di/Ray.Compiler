<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
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
    public function __invoke($object, string $prop, $default = null)
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

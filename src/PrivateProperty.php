<?php

declare(strict_types=1);

namespace Ray\Compiler;

final class PrivateProperty
{
    /**
     * @param object $object
     *
     * @return null|mixed
     */
    public function __invoke($object, string $prop, $default = null)
    {
        try {
            $refProp = (new \ReflectionProperty($object, $prop));
        } catch (\Exception $e) {
            return $default;
        }
        $refProp->setAccessible(true);

        return $refProp->getValue($object);
    }
}

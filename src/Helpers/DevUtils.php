<?php

namespace LeKoala\DevToolkit\Helpers;

use ReflectionObject;

class DevUtils
{
    /**
     * @param object $obj
     * @param string $prop
     * @param mixed $val
     * @return void
     */
    public static function updateProp(object $obj, string $prop, $val): void
    {
        $refObject = new ReflectionObject($obj);
        $refProperty = $refObject->getProperty($prop);
        $refProperty->setAccessible(true);
        $refProperty->setValue($obj, $val);
    }

    /**
     * @param object $obj
     * @param string $prop
     * @param callable $cb
     * @return void
     */
    public static function updatePropCb(object $obj, string $prop, callable $cb): void
    {
        $refObject = new ReflectionObject($obj);
        $refProperty = $refObject->getProperty($prop);
        $refProperty->setAccessible(true);
        $refProperty->setValue($obj, $cb($refProperty->getValue($obj)));
        d($obj);
    }

    /**
     * @param object $obj
     * @param string $prop
     * @return mixed
     */
    public static function getProp(object $obj, string $prop)
    {
        $refObject = new ReflectionObject($obj);
        $refProperty = $refObject->getProperty($prop);
        $refProperty->setAccessible(true);
        return $refProperty->getValue($obj);
    }
}

<?php

namespace LeKoala\DevToolkit\Helpers;

use ReflectionObject;
use ReflectionClass;

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

    /**
     * @param string $class
     * @param string $prop
     * @return mixed
     */
    public static function getStaticProp(string $class, string $prop)
    {
        $refClass = new ReflectionClass($class);
        return $refClass->getStaticPropertyValue($prop);
    }

    /**
     * @param string $class
     * @param string $prop
     * @param mixed $val
     * @return void
     */
    public static function updateStaticProp(string $class, string $prop, $val)
    {
        $refClass = new ReflectionClass($class);
        $refClass->setStaticPropertyValue($prop, $val);
    }
}

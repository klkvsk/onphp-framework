<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2018-01-26
 */

abstract class LightMetaPropertyValueMapper
{
    public function matchType($type)
    {
        return false;
    }

    public function matchClassName($className)
    {
        return false;
    }

    abstract public function map($value);
}
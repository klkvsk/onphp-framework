<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-20
 */
interface MappedEntity
{
    /**
     * @return BasePrimitive[]
     */
    public static function getMapping();
}
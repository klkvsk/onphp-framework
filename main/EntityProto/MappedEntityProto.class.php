<?php

/**
 * Quick and easy use for entity proto
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-20
 */
class MappedEntityProto extends EntityProto
{
    protected $className;

    public static function of($className)
    {
        return new static($className);
    }

    protected function __construct($className)
    {
        $this->className = $className;
    }

    public function baseProto()
    {
        // use OOP-based mapping inheritance instead
        return null;
    }

    public function className()
    {
        return $this->className;
    }

    public function getFormMapping()
    {
        /** @var MappedEntity|string $class */
        $class = $this->className;
        $mapping = $class::getMapping();
        return $mapping;
    }

}
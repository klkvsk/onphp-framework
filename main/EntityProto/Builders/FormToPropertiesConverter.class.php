<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-21
 */
class FormToPropertiesConverter extends ObjectBuilder
{
    /**
     * @return static
     **/
    public static function create(EntityProto $proto)
    {
        return new self($proto);
    }

    protected function createEmpty()
    {
        return $this->proto->createObject();
    }

    /**
     * @return FormGetter
     **/
    protected function getGetter($object)
    {
        return new FormGetter($this->proto, $object);
    }

    /**
     * @return PropertySetter
     **/
    protected function getSetter(&$object)
    {
        return new PropertySetter($this->proto, $object);
    }
}
<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-21
 */
class PropertySetter extends PrototypedSetter
{
    private $getter = null;

    public function __construct(EntityProto $proto, $object)
    {
        if (is_array($object)) {
            xdebug_break();
        }
        Assert::isTrue(is_object($object), gettype($object));

        parent::__construct($proto, $object);
    }

    public function set($name, $value)
    {
        if (!isset($this->mapping[$name]))
            throw new WrongArgumentException(
                "knows nothing about property '{$name}'"
            );

        $primitive = $this->mapping[$name];
        $this->object->{$primitive->getName()} = $value;

        return $this;
    }

    /**
     * @return ScopeGetter
     **/
    public function getGetter()
    {
        if (!$this->getter) {
            $this->getter = new PropertyGetter($this->proto, $this->object);
        }

        return $this->getter;
    }
}
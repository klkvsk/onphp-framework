<?php

/**
 * Class PrototypedObjectCheckpoint
 * Checks clean/dirty state of object
 */
class PrototypedObjectCheckpoint
{
    /** @var Prototyped */
    protected $clone;
    /** @var Prototyped */
    protected $object;

    /**
     * PrototypedObjectCheckpoint constructor.
     * @param Prototyped $object
     */
    public function __construct(Prototyped $object)
    {
        $this->clone = clone $object;
        $this->object = $object;
    }

    /**
     * @return bool
     */
    public function isObjectModified()
    {
        foreach ($this->getPropertyList() as $property) {
            if ($this->_isPropertyModified($property)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getModifiedProperties()
    {
        $modifiedPropertyNames = [];
        foreach ($this->getPropertyList() as $property) {
            if ($this->_isPropertyModified($property)) {
                $modifiedPropertyNames [] = $property->getName();
            }
        }
        return $modifiedPropertyNames;
    }

    /**
     * @param $propertyName
     * @return bool
     * @throws WrongArgumentException
     */
    public function isPropertyModified($propertyName)
    {
        $property = $this->object->proto()->getPropertyByName($propertyName);
        if ($property->getRelationId() == MetaRelation::ONE_TO_MANY
            || $property->getRelationId() == MetaRelation::MANY_TO_MANY
        ) {
            throw new WrongArgumentException('checking x-to-many relations is not supported');
        }
        return $this->_isPropertyModified($property);
    }

    public function getOldValue($propertyName)
    {
        return PrototypeUtils::getValue($this->clone, $propertyName);
    }

    public function getNewValue($propertyName)
    {
        return PrototypeUtils::getValue($this->object, $propertyName);
    }

    /**
     * @param LightMetaProperty $property
     * @return bool|null
     */
    protected function _isPropertyModified(LightMetaProperty $property)
    {
        // обычные свойства
        if ($property->getRelationId() == null) {
            $getter = $property->getGetter();
        } else if ($property->getRelationId() == MetaRelation::ONE_TO_ONE) {
            $getter = $property->getGetter() . 'Id';
        } else {
            return null;
        }

        $valueA = $this->object->{$getter}();
        $valueB = $this->clone->{$getter}();

        if ($valueA instanceof Date && $valueB instanceof Date) {
            $valueA = $valueA->toStamp();
            $valueB = $valueB->toStamp();
        }
        if (in_array($property->getType(), ['integer', 'enum', 'enumeration', 'integerIdentifier'])) {
            $valueA = ($valueA === null) ? null : (int)$valueA;
            $valueB = ($valueB === null) ? null : (int)$valueB;
        }
        if (in_array($property->getType(), ['float'])) {
            $valueA = ($valueA === null) ? null : (float)$valueA;
            $valueB = ($valueB === null) ? null : (float)$valueB;
        }

        return $valueA !== $valueB;
    }

    /**
     * @return LightMetaProperty[]
     */
    protected function getPropertyList()
    {
        return $this->object->proto()->getPropertyList();
    }

    /**
     * @return void
     */
    public function revertModifications()
    {
        PrototypeUtils::copy($this->clone, $this->object, $this->getPropertyList());
    }

}
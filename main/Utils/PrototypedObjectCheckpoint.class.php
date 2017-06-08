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
            if ($this->isPropertyModified($property)) {
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
            if ($this->isPropertyModified($property)) {
                $modifiedPropertyNames [] = $property->getName();
            }
        }
        return $modifiedPropertyNames;
    }

    /**
     * @param string $propertyName
     * @return bool
     * @throws WrongArgumentException
     */
    public function isPropertyModified($propertyName)
    {
        return $this->extractOldValue($propertyName) !== $this->extractNewValue($propertyName);
    }

    public function getOldValue($propertyName)
    {
        return PrototypeUtils::getValue($this->clone, $propertyName);
    }

    public function getNewValue($propertyName)
    {
        return PrototypeUtils::getValue($this->object, $propertyName);
    }

    public function extractOldValue($propertyName)
    {
        return $this->extractValue($this->clone, $propertyName);
    }

    public function extractNewValue($propertyName)
    {
        return $this->extractValue($this->object, $propertyName);
    }

    protected function extractValue(Prototyped $object, $propertyName) {
        if ($propertyName instanceof LightMetaProperty) {
            $property = $propertyName;
        } else {
            $property = $object->proto()->getPropertyByName($propertyName);
        }

        if ($property->getRelationId() == MetaRelation::ONE_TO_MANY
            || $property->getRelationId() == MetaRelation::MANY_TO_MANY
        ) {
            throw new WrongArgumentException('checking x-to-many relations is not supported');
        }

        // обычные свойства
        if ($property->getRelationId() == null) {
            $getter = $property->getGetter();
        } else if ($property->getRelationId() == MetaRelation::ONE_TO_ONE) {
            $getter = $property->getGetter() . 'Id';
        } else {
            return null;
        }

        $value = $object->{$getter}();

        if ($value instanceof Date) {
            return $value->toStamp();
        } else if (in_array($property->getType(), ['integer', 'enum', 'enumeration', 'integerIdentifier'])) {
            return ($value === null) ? null : (int)$value;
        } else if (in_array($property->getType(), ['float'])) {
            return ($value === null) ? null : (float)$value;
        } else {
            return $value;
        }
    }

    /**
     * @return LightMetaProperty[]
     */
    protected function getPropertyList()
    {
        return array_filter(
            $this->object->proto()->getPropertyList(),
            function (LightMetaProperty $property) {
                return $property->getRelationId() != MetaRelation::ONE_TO_MANY
                    && $property->getRelationId() != MetaRelation::MANY_TO_MANY;
            }
        );
    }

    /**
     * @return void
     */
    public function revertModifications()
    {
        PrototypeUtils::copy($this->clone, $this->object, $this->getPropertyList());
    }

}
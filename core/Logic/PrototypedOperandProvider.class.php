<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-02-15
 */
class PrototypedOperandProvider implements LogicalOperandProvider
{
    /** @var Prototyped */
    protected $object;

    public function __construct(Prototyped $object)
    {
        $this->object = $object;
    }

    public function toOperand($value)
    {
        if (is_scalar($value)) {
            try {
                return PrototypeUtils::getValue($this->object, $value);
            } catch (ObjectNotFoundException $e) {
                return $value;
            }
        } else if ($value instanceof LogicalObject) {
            return $value->toBoolean($this);
        }
        return $value;
    }
}
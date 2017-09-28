<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-09-28
 */

class DBCast extends Castable implements MappableObject
{
    protected $value;

    public function __construct($value, $castTo)
    {
        $this->value = $value;
        $this->castTo($castTo);
    }

    /**
     * @param $value
     * @return DBCast
     */
    public static function create($value, $castTo)
    {
        return new self($value, $castTo);
    }

    public function toMapped(ProtoDAO $dao, JoinCapableQuery $query)
    {
        if ($this->value instanceof MappableObject)
            $value = $this->value->toMapped($dao, $query);
        else
            $value = $dao->guessAtom($this->value, $query);
        return new self($value, $this->cast);
    }

    public function toDialectString(Dialect $dialect)
    {
        if ($this->value instanceof DialectString) {
            $value = $this->value->toDialectString($dialect);
        } else {
            $value = $dialect->valueToString($dialect);
        }
        return $dialect::toCasted($value, $this->cast);
    }


}
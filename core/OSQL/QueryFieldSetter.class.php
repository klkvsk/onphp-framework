<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2018-01-26
 */

abstract class QueryFieldSetter
{
    /**
     * @param $value boolean
     */
    abstract public function match($value);

    /**
     * @param $value mixed
     * @return mixed
     */
    public function prepare($value)
    {
        return $value;
    }

    /**
     * @param InsertOrUpdateQuery $query
     * @param string $field
     * @param mixed  $value
     * @return void
     */
    public function set(InsertOrUpdateQuery $query, $field, $value)
    {
        $query->set($field, $this->prepare($value));
    }
}
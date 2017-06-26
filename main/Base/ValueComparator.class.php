<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-21
 */
class ValueComparator extends Singleton implements Comparator, Instantiatable
{
    public function compare($one, $two)
    {
        $cmpOne = $this->toComparable($one);
        $cmpTwo = $this->toComparable($two);

        if ($cmpOne == $cmpTwo)
            return 0;

        return ($cmpOne < $cmpTwo) ? -1 : 1;
    }

    public function toComparable($value) {
        if ($value instanceof Identifiable) {
            return $value->getId();
        }
        if ($value instanceof Date) {
            return $value->toStamp();
        }
        return $value;
    }
}

<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-21
 */
class ValueComparator extends Singleton implements Comparator, Instantiatable
{
    /**
     * @param $one
     * @param $two
     * @return int
     */
    public function compare($one, $two)
    {
        if (is_array($one) ^ is_array($two)) {
            return is_array($one) ? 1 : -1;
        }
        if (is_array($one) && is_array($two)) {
            $cmpKeys = $this->compareArrays(array_keys($one), array_keys($two));
            if ($cmpKeys !== 0) {
                return $cmpKeys;
            } else {
                return $this->compareArrays(array_values($one), array_values($two));
            }
        }

        if (is_null($one) ^ is_null($two)) {
            return is_null($one) ? -1 : 1;
        }

        $cmpOne = $this->toComparable($one);
        $cmpTwo = $this->toComparable($two);

        if (is_string($cmpOne) ^ is_string($cmpTwo)) {
            return is_string($cmpOne) ? 1 : -1;
        }

        if ($cmpOne === $cmpTwo)
            return 0;

        return ($cmpOne < $cmpTwo) ? -1 : 1;
    }

    /**
     * Strictly ordinal arrays
     * @param array $one
     * @param array $two
     * @return int
     */
    public function compareArrays(array $one, array $two)
    {
        for ($i = 0; $i < max(count($one), count($two)); $i++) {
            $a = isset($one[$i]) ? $one[$i] : null;
            $b = isset($two[$i]) ? $two[$i] : null;
            $cmp = $this->compare($a, $b);
            if ($cmp !== 0) {
                return $cmp;
            }
        }

        return 0;
    }

    public function toComparable($value) {
        if ($value instanceof Identifiable) {
            return $value->getId();
        }
        if ($value instanceof Date) {
            return $value->toStamp();
        }
        if (is_numeric($value)) {
            $value = doubleval($value);
        }
        return $value;
    }
}

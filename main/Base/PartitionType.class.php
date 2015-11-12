<?php
/**
 * 
 * @author Соломонов Алексей <byorty@mail.ru>
 * @date 2015.04.21.04.15
 */

class PartitionType extends Enum {

    const
        DATE = 1
    ;

    protected static $names = [
        self::DATE => 'По дате',
    ];

    private static $tableNames = [
        self::DATE => 'createDateTableName',
    ];

    private static $ranges = [
        self::DATE => 'rangeDates',
    ];

    private static $nexts = [
        self::DATE => 'nextDate',
    ];

    public function createTableName($value) {
        $func = static::$tableNames[$this->id];
        return $this->$func($value);
    }

    private function createDateTableName(Date $date) {
        $date = clone $date;
        return $date
            ->modify('first day of this month')
            ->toFormatString('Y_m_d')
        ;
    }

    public function range($min, $max) {
        $func = static::$ranges[$this->id];
        return $this->$func($min, $max);
    }

    private function rangeDates(Date $from, Date $till) {
        /** @var Datetime $fromDatetime */
        $fromDatetime = Date::create($from->toStamp())->getDateTime()->modify('first day of this month');
        /** @var Datetime $tillDatetime */
        $tillDatetime = Date::create($till->toStamp())->getDateTime()->modify('last day of this month');
        /** @var DateInterval $diff */
        $diff = $tillDatetime->diff($fromDatetime);
        $lastMonthIndex = ($diff->format('%y') * 12 + $diff->format('%m')) - 1;
        /** @var Datetime[] $range */
        $range = new DatePeriod(
            $fromDatetime,
            new DateInterval('P1M'),
            $tillDatetime
        );

        $dates = [];
        foreach ($range as $i => $date) {
            $cloneDate = clone $date;
            if ($i == 0) {
                $dates[] = Date::create($from->toStamp());
                $dates[] = Date::create($cloneDate->modify('last day of this month')->getTimestamp());
            } else if ($i == $lastMonthIndex) {
                $dates[] = Date::create($cloneDate->modify('first day of this month')->getTimestamp());
                $dates[] = Date::create($till->toStamp());
            } else {
                $dates[] = Date::create($date->modify('first day of this month')->getTimestamp());
                $dates[] = Date::create($date->modify('last day of this month')->getTimestamp());
            }
        }

        return $dates;
    }

    public function next($value) {
        $func = static::$nexts[$this->id];
        return $this->$func($value);
    }

    private function nextDate(Date $today) {
        $today = clone $today;
        return $today->modify('first day of next month');
    }

    /**
     * @return static
     */
    public static function date() {
        return static::create(static::DATE);
    }
}
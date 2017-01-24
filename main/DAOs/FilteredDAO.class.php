<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-01-12
 */
interface FilteredDAO
{
    /**
     * @return LogicalObject
     */
    public function getFilterLogic();

}
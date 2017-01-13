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

    /**
     * @param LogicalObject $logic
     * @return $this
     */
    public function setFilterLogic(LogicalObject $logic);

    /**
     * @return $this
     */
    public function dropFilterLogic();

    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    public function filterSelectQuery(SelectQuery $query);

}
<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-09-08
 */

interface ShardingStrategy
{
    /**
     * @return string
     */
    public function getTableName();

    /**
     * @return string
     */
    public function getShardingKey();

    /**
     * @param QuerySkeleton $query
     * @return int[]
     */
    public function getShardIdsByWhereClause(QuerySkeleton $query);

    /**
     * @param InsertOrUpdateQuery $query
     * @return int
     */
    public function getShardIdByFieldValue(InsertOrUpdateQuery $query);

    /**
     * @param $value
     * @return int
     */
    public function chooseShard($value);

    /**
     * @param Range $values
     * @return int[]
     */
    public function chooseShards(Range $values);
}
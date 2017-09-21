<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-08-23
 */

interface DBInterface
{
    /**
     * @return $this
     */
    public function connect();

    /**
     * @return $this
     */
    public function disconnect();

    /**
     * @return boolean
     */
    public function isConnected();

    /**
     * @return Dialect
     */
    public function getDialect();

    /**
     * @param $table
     * @return DBTable
     */
    public function getTableInfo($table);

    /**
     * @param $queryString
     * @return resource|null
     */
    public function queryRaw($queryString);

    public function query(Query $query);
    public function queryRow(Query $query);
    public function querySet(Query $query);
    public function queryNumRows(Query $query);
    public function queryColumn(Query $query);
    public function queryCount(Query $query);
    public function queryNull(Query $query);

    /**
     * @param IsolationLevel|null $level
     * @param AccessMode|null $mode
     * @return $this
     */
    public function begin(IsolationLevel $level = null, AccessMode $mode = null);

    /**
     * @return $this
     */
    public function commit();

    /**
     * @return $this
     */
    public function rollback();

    /**
     * @return boolean
     */
    public function inTransaction();

    /**
     * @return resource|null
     */
    public function getLink();

    /**
     * @param callable $callback
     * @return $this
     */
    public function runAfterCommit($callback);

    /**
     * @param callable $callback
     * @return $this
     */
    public function runOnRollback($callback);

    /**
     * @param $sequence
     * @return mixed
     */
    public function obtainSequence($sequence);

    /** @return boolean */
    public function hasSequences();

    /** @return boolean */
    public function hasQueue();

    /** @return $this **/
    public function queueStart();

    /** @return $this **/
    public function queueStop();

    /** @return $this **/
    public function queueDrop();

    /** @return $this **/
    public function queueFlush();

    /** @return boolean */
    public function isQueueActive();

}
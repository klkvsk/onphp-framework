<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-08-29
 */

abstract class MultiDB implements DBInterface
{
    /** @var BaseLocker */
    protected $tableLocker;

    /**
     * @param BaseLocker $tableLocker
     * @return $this
     */
    public function setTableLocker($tableLocker)
    {
        $this->tableLocker = $tableLocker;
        return $this;
    }

    /**
     * @return BaseLocker
     */
    public function getTableLocker()
    {
        return $this->tableLocker;
    }

    /**
     * @return DBInterface[]
     */
    abstract protected function getEndpoints();

    /**
     * @return DBInterface
     */
    abstract protected function getDefaultEndpoint();

    
    public function connect()
    {
        foreach ($this->getEndpoints() as $db) {
            if (!$db->isConnected()) {
                $db->connect();
            }
        }
    }

    public function disconnect()
    {
        foreach ($this->getEndpoints() as $db) {
            if ($db->isConnected()) {
                $db->disconnect();
            }
        }
        if ($this->getTableLocker()) {
            $this->getTableLocker()->clean();
        }
    }

    public function isConnected($all = false)
    {
        if (empty($this->getEndpoints())) {
            return false;
        }

        $connected = true;
        foreach ($this->getEndpoints() as $db) {
            $connected = $connected && $db->isConnected();
            if (!$all && $connected) {
                // at least one is connected
                return true;
            }
        }

        return $connected;
    }

    public function getDialect()
    {
        return $this->getDefaultEndpoint()->getDialect();
    }

    public function getTableInfo($table)
    {
        return $this->getDefaultEndpoint()->getTableInfo($table);
    }

    public function obtainSequence($sequence)
    {
        return $this->getDefaultEndpoint()->obtainSequence($sequence);
    }

    public function hasSequences()
    {
        return $this->getDefaultEndpoint()->hasSequences();
    }

    public function hasQueue()
    {
        return false;
    }

    public function queueStart()
    {
        throw new UnimplementedFeatureException();
    }

    public function queueStop()
    {
        throw new UnimplementedFeatureException();
    }

    public function queueDrop()
    {
        throw new UnimplementedFeatureException();
    }

    public function queueFlush()
    {
        throw new UnimplementedFeatureException();
    }

    public function isQueueActive()
    {
        return false;
    }


    public function begin(IsolationLevel $level = null, AccessMode $mode = null)
    {
        $this->assertEndpoints();
        foreach ($this->getEndpoints() as $db) {
            $db->begin($level, $mode);
        }
        return $this;
    }

    public function rollback()
    {
        $this->assertEndpoints();
        foreach ($this->getEndpoints() as $db) {
            $db->rollback();
        }
        return $this;
    }

    public function commit()
    {
        $this->assertEndpoints();
        foreach ($this->getEndpoints() as $db) {
            $db->commit();
        }
        return $this;
    }

    public function inTransaction($all = false)
    {
        $this->assertEndpoints();

        $inTransaction = true;
        foreach ($this->getEndpoints() as $db) {
            $inTransaction = $inTransaction && $db->inTransaction();
            if (!$all && $inTransaction) {
                // at least one is in transaction
                return true;
            }
        }

        return $inTransaction;
    }

    public function getLink()
    {
        throw new UnimplementedFeatureException();
    }

    public function runAfterCommit($callback)
    {
        return $this->getDefaultEndpoint()->runAfterCommit($callback);
    }

    public function runOnRollback($callback)
    {
        return $this->getDefaultEndpoint()->runOnRollback($callback);
    }

    protected function assertEndpoints() {
        $dbs = $this->getEndpoints();
        Assert::isNotEmptyArray($dbs, self::class . ' was not set with uplink databases');
    }

}
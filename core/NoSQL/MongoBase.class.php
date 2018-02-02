<?php

use MongoDB\BSON\ObjectID;

/**
 * MongoDB connector for driver v3+
 *
 * @see http://www.mongodb.org/
 *
 * @ingroup NoSQL
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2018.02.02
 */
class MongoBase extends NoSQL
{
    const C_TABLE   = 1001;
    const C_FIELDS  = 1002;
    const C_QUERY   = 1003;
    const C_ORDER   = 1004;
    const C_LIMIT   = 1005;
    const C_SKIP    = 1006;

    /** @var string|null */
    protected $connectionString = null;

    /** @var array|null */
    protected $connectionOptions = null;

    /** @var MongoDB\Client */
    protected $link = null;

    /** @var MongoDB\Database */
    protected $db = null;

    /** @var int|string  */
    protected $safeWriteConcern = 1;

    /**
     * @return MongoBase
     * @throws NoSQLException
     */
    public function connect()
    {
        if (empty($this->connectionString)) {
            $conn =
                'mongodb://'
                . ($this->username && $this->password ? "{$this->username}:{$this->password}@" : null)
                . $this->hostname
                . ($this->port ? ":{$this->port}" : null);
        } else {
            preg_match('#(.+)/(\w+)#', $this->connectionString, $matches);
            $conn = $matches[1];
            $base = $matches[2];
            $this->setBasename($base);
        }

        $this->link = new MongoDB\Client($conn, $this->connectionOptions);
        $this->db = $this->link->selectDatabase($this->basename);

        return $this;
    }

    public function switchToPrimary()
    {
        $this->connectionOptions['safe'] = true;
        $this->connectionOptions['slaveOkay'] = false;
        $this->connectionOptions['readPreference'] = 'primary';
        $this->connect();
    }

    /**
     * @return MongoBase
     */
    public function disconnect()
    {
        $this->link = null;
        $this->db = null;

        return $this;
    }

    /**
     * @return \MongoDB\Client|null
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->link !== null;
    }

    /**
     * @param $connectionString
     * @return MongoBase
     */
    public function setConnectionString($connectionString)
    {
        $this->connectionString = $connectionString;

        return $this;
    }

    /**
     * @param $connectionOptions
     * @return MongoBase
     */
    public function setConnectionOptions($connectionOptions)
    {
        $this->connectionOptions = $connectionOptions;

        return $this;
    }

    /**
     * @param string $sequence
     * @return ObjectID
     */
    public function obtainSequence($sequence)
    {
        return $this->makeId();
    }

    /**
     * @param string $table
     * @param string $id
     * @return array
     * @throws ObjectNotFoundException
     */
    public function selectOne($table, $id)
    {
        $rows = $this->mongoFind($table, [ '_id' => $this->makeId($id) ]);

        if (empty($rows)) {
            throw new ObjectNotFoundException('Object with id "' . $id . '" in table "' . $table . '" not found!');
        }

        return reset($rows);
    }

    /**
     * @param string $table
     * @param string[] $ids
     * @return array[]
     */
    public function selectList($table, array $ids)
    {
        return $this->mongoFind($table, [ '_id' => [ '$in' => $this->makeIdList($ids) ] ]);
    }

    /**
     * @param string $table
     * @param array $row
     * @param array $options
     * @return array
     */
    public function insert($table, array $row, $options = [])
    {
        $row = $this->encodeRow($row);

        $options = $this->parseOptions($options);

        $result = $this->db
            ->selectCollection($table)
            ->insertOne($row, $options);

        if ($result->isAcknowledged()) {
            $row['_id'] = $result->getInsertedId();
        }

        return $this->decodeRow($row);
    }

    /**
     * @param string $table
     * @param array[] $rows
     * @param array $options
     * @return array[]
     * @throws WrongStateException
     */
    public function batchInsert($table, array $rows, array $options = [])
    {
        $rows = array_map(function ($row) { return $this->encodeRow($row); }, $rows);

        $options = $this->parseOptions($options);

        $result = $this->db
            ->selectCollection($table)
            ->insertMany($rows, $options);

        if ($result->isAcknowledged()) {
            $ids = $result->getInsertedIds();

            if ($result->getInsertedCount() != count($rows)) {
                throw new WrongStateException('not all objects were inserted, only: ' . implode(', ', $ids));
            }

            foreach ($rows as &$row) {
                $row['_id'] = array_shift($ids);
                $row = $this->decodeRow($row);
            }
        }

        return $rows;
    }

    /**
     * @param $table
     * @param array $row
     * @param array $options
     * @return array
     * @throws NoSQLException
     * @throws WrongArgumentException
     */
    public function update($table, array $row, $options = [])
    {
        $row = $this->encodeRow($row);

        $id = isset($row['_id']) ? $row['_id'] : null;

        $options = $this->parseOptions($options);

        if (isset($options['where'])) {
            if (is_array($options['where'])) {
                $where = $options['where'];
            }
            unset($options['where']);

        } else if ($id !== null) {
            $where = ['_id' => $id];
        }

        if (empty($where)) {
            throw new NoSQLException('empty "where" clause for update');
        }

        $isUpsert = isset($options['upsert']) && $options['upsert'] == true;

        $result = $this->db
            ->selectCollection($table)
            ->replaceOne($where, $row, $options);

        if ($result->isAcknowledged()) {
            $countUpdated = $isUpsert
                ? $result->getUpsertedCount()
                : $result->getModifiedCount();
            if ($countUpdated != 1) {
                throw new WrongArgumentException($countUpdated . ' rows updated: racy or insane inject happened');
            }

            if ($isUpsert) {
                $row['_id'] = $result->getUpsertedId();
            }
        }

        return $this->decodeRow($row);
    }

    /**
     * @return int|string
     */
    public function getSafeWriteConcern()
    {
        return $this->safeWriteConcern;
    }

    /**
     * @param int|string $safeWriteConcern
     * @return $this
     */
    public function setSafeWriteConcern($safeWriteConcern)
    {
        $this->safeWriteConcern = $safeWriteConcern;
        return $this;
    }

    /**
     * @param \MongoDB\Driver\WriteResult $result
     * @throws DatabaseException
     */
    protected function checkResult(\MongoDB\Driver\WriteResult $result)
    {
        if ($result->getWriteConcernError() || $result->getWriteErrors()) {
            throw new DatabaseException(
                'Mongo writeResult errors: ',
                print_r([
                    'writeConcernError' => $result->getWriteConcernError(),
                    'writeErrors' => $result->getWriteErrors()
                ], true)
            );
        }
    }

    /**
     * @param string $table
     * @param string $id
     * @throws WrongStateException
     */
    public function deleteOne($table, $id)
    {
        $result = $this->db
            ->selectCollection($table)
            ->deleteOne(['_id' => $this->makeId($id)]);

        if ($result->isAcknowledged() && $result->getDeletedCount() != 1) {
            throw new WrongStateException('no object were dropped');
        }
    }

    /**
     * @param string $table
     * @param string[] $ids
     * @throws WrongStateException
     */
    public function deleteList($table, array $ids)
    {
        $result = $this->db
            ->selectCollection($table)
            ->deleteMany(['_id' => ['$in' => $this->makeIdList($ids)]]);

        if ($result->isAcknowledged() && $result->getDeletedCount() != count($ids)) {
            throw new WrongStateException('not all objects were dropped');
        }
    }

    /**
     * @param string $table
     * @return array[]
     */
    public function getPlainList($table)
    {
        return $this->mongoFind($table);
    }

    /**
     * @param string $table
     * @return int
     */
    public function getTotalCount($table)
    {
        return $this->db
            ->selectCollection($table)
            ->count();
    }

    /**
     * @param string $table
     * @param string $field
     * @param mixed  $value
     * @param Criteria|null $criteria
     * @return int
     */
    public function getCountByField($table, $field, $value, Criteria $criteria = null)
    {
        if (Assert::checkInteger($value)) {
            $value = (int)$value;
        }
        $options = $this->parseCriteria($criteria);

        return $this->mongoCount(
            $table, [$field => $value], ['_id'],
            $options[self::C_ORDER], $options[self::C_LIMIT], $options[self::C_SKIP]
        );
    }

    /**
     * @param string $table
     * @param string $field
     * @param mixed  $value
     * @param Criteria|null $criteria
     * @return array[]
     */
    public function getListByField($table, $field, $value, Criteria $criteria = null)
    {
        if (Assert::checkInteger($value)) {
            $value = (int)$value;
        }
        $options = $this->parseCriteria($criteria);

        return $this->mongoFind(
            $table, [$field => $value], $options[self::C_FIELDS],
            $options[self::C_ORDER], $options[self::C_LIMIT], $options[self::C_SKIP]
        );
    }

    /**
     * @param string $table
     * @param string $field
     * @param mixed  $value
     * @param Criteria|null $criteria
     * @return array[]
     */
    public function getIdListByField($table, $field, $value, Criteria $criteria = null)
    {
        if (Assert::checkInteger($value)) {
            $value = (int)$value;
        }
        $options = $this->parseCriteria($criteria);

        $rows = $this->mongoFind(
            $table, [$field => $value], ['_id'],
            $options[self::C_ORDER], $options[self::C_LIMIT], $options[self::C_SKIP]
        );

        return array_map(
            function ($row) { return $row['id']; },
            $rows
        );
    }

    /**
     * @param string $table
     * @param array  $query
     * @return array[]
     */
    public function find($table, $query)
    {
        return $this->mongoFind($table, $query);
    }

    /**
     * @param Criteria $criteria
     * @return array[]
     */
    public function findByCriteria(Criteria $criteria)
    {
        $options = $this->parseCriteria($criteria);

        return $this->mongoFind(
            $options[self::C_TABLE], $options[self::C_QUERY], $options[self::C_FIELDS],
            $options[self::C_ORDER], $options[self::C_LIMIT], $options[self::C_SKIP]
        );
    }

    /**
     * @param Criteria $criteria
     * @return int
     */
    public function countByCriteria(Criteria $criteria)
    {
        $options = $this->parseCriteria($criteria);

        return $this->mongoCount(
            $options[self::C_TABLE], $options[self::C_QUERY], [],
            $options[self::C_ORDER], $options[self::C_LIMIT], $options[self::C_SKIP]
        );
    }


    public function deleteByCriteria(Criteria $criteria, array $options = [])
    {
        $query = $this->parseCriteria($criteria);

        $this->mongoDelete($query[self::C_TABLE], $query[self::C_QUERY], $options);
    }

    /**
     * @param Criteria $criteria
     * @return \MongoDB\Driver\Cursor
     * @throws NoSQLException
     * @throws WrongStateException
     */
    public function makeCursorByCriteria(Criteria $criteria)
    {
        $options = $this->parseCriteria($criteria);

        if (!isset($options[self::C_TABLE])) {
            throw new NoSQLException('Can not find without table!');
        }

        return $this->db->selectCollection($options[self::C_TABLE])->find(
            $options[self::C_QUERY],
            $this->mongoMakeFindOptions(
                $options[self::C_FIELDS],
                $options[self::C_ORDER],
                $options[self::C_LIMIT],
                $options[self::C_SKIP])
        );
    }

    /**
     * @param string     $table
     * @param array      $query
     * @param array      $fields
     * @param array|null $order
     * @param null       $limit
     * @param null       $skip
     * @return array[]
     * @throws NoSQLException
     */
    protected function mongoFind($table, array $query = [], array $fields = [], array $order = null, $limit = null, $skip = null)
    {
        if (!$table) {
            throw new NoSQLException('Can not find without table!');
        }

        $options = $this->mongoMakeFindOptions($fields, $order, $limit, $skip);

        $cursor = $this->db
            ->selectCollection($table)
            ->find($query, $options);

        $rows = [];
        foreach ($cursor as $row) {
            $rows[] = $this->decodeRow($row);
        }

        return $rows;
    }

    /**
     * @param $table
     * @param array      $query
     * @param array      $fields
     * @param null|array $order
     * @param null|int   $limit
     * @param null|int   $skip
     * @return int
     */
    protected function mongoCount($table, array $query, array $fields = [], array $order = null, $limit = null, $skip = null)
    {
        $options = $this->mongoMakeFindOptions($fields, $order, $limit, $skip);

        return $this->db
            ->selectCollection($table)
            ->count($query, $options);
    }

    /**
     * @param string $table
     * @param array  $query
     * @param array  $options
     */
    protected function mongoDelete($table, array $query, array $options)
    {
        $this->db
            ->selectCollection($table)
            ->deleteMany($query, $options);
    }

    /**
     * @param array $fields
     * @param array $order
     * @param int $limit
     * @param int $skip
     * @return array
     */
    protected function mongoMakeFindOptions(array $fields = [], array $order = null, $limit = null, $skip = null)
    {
        $options = [];
        if ($fields) {
            $options['projection'] = $fields;
        }
        if ($skip) {
            $options['skip'] = $skip;
        }
        if ($limit) {
            $options['limit'] = $limit;
        }
        if ($order) {
            $options['sort'] = $order;
        }

        return $options;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function encodeRow(array $row)
    {
        if (isset($row['id'])) {
            $row['_id'] = $this->makeId($row['id']);
        }
        unset($row['id']);

        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function decodeRow($row)
    {
        if ($row instanceof \MongoDB\Model\BSONDocument) {
            $row = $row->getArrayCopy();
        }
        array_walk_recursive($row, function (&$item) {
            if ($item instanceof \MongoDB\Model\BSONDocument) {
                $item = $item->getArrayCopy();
            } else if ($item instanceof \MongoDB\Model\BSONArray) {
                $item = $item->getArrayCopy();
            } else if ($item instanceof \MongoDB\BSON\ObjectID) {
                $item = (string)$item;
            } else if (!is_scalar($item) && !is_array($item) && !is_null($item)) {
                throw new UnexpectedValueException(var_export($item, true));
            }
        });

        $row['id'] = (string)$row['_id'];
        unset($row['_id']);

        return $row;
    }

    /**
     * @param null|string|ObjectID $key
     * @return ObjectID
     */
    protected function makeId($key = null)
    {
        return ($key instanceof ObjectID) ? $key : new ObjectID($key);
    }

    /**
     * @param string[]|ObjectID[] $keys
     * @return ObjectID[]
     */
    protected function makeIdList(array $keys)
    {
        $fields = [];
        foreach ($keys as $key) {
            $fields[] = $this->makeId($key);
        }

        return $fields;
    }

    /**
     * Prepare query options using criteria
     * @param Criteria $criteria
     * @return array
     * @throws WrongStateException
     */
    protected function parseCriteria(Criteria $criteria = null)
    {
        $options = [
            self::C_TABLE   => null,
            self::C_FIELDS  => [],
            self::C_QUERY   => [],
            self::C_ORDER   => [],
            self::C_LIMIT   => null,
            self::C_SKIP    => null,
        ];

        if (! $criteria) {
            return $options;
        }

        if ($criteria->getDao()) {
            $options[self::C_TABLE] = $criteria->getDao()->getTable();
        } else {
            $options[self::C_TABLE] = 'foo_test';
        }

        foreach ($criteria->getLogic()->getChain() as $expression) {
            if ($expression instanceof NoSQLExpression) {
                $options[self::C_FIELDS] = array_merge(
                    $options[self::C_FIELDS],
                    $expression->getFieldList()
                );
                $options[self::C_QUERY] = array_merge(
                    $options[self::C_QUERY],
                    $expression->toMongoQuery()
                );
            } else {
                throw new UnexpectedValueException(print_r($expression, true));
            }
        }

        foreach ($criteria->getOrder()->getList() as $orderBy) {
            $options[self::C_ORDER] = array_merge(
                $options[self::C_ORDER],
                [ $orderBy->getFieldName() => $orderBy->isAsc() ? 1 : -1 ]
            );
        }

        if ($criteria->getLimit()) {
            $options[self::C_LIMIT] = $criteria->getLimit();
        }

        if ($criteria->getOffset()) {
            $options[self::C_SKIP] = $criteria->getOffset();
        }

        return $options;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function parseOptions(array $options)
    {
        if (isset($options['safe'])) {
            $options['writeConcern'] = $options['safe']
                ? new \MongoDB\Driver\WriteConcern($this->safeWriteConcern)
                : new \MongoDB\Driver\WriteConcern(0);
            unset($options['safe']);
        }
        return $options;
    }

    /**
     * Raw access to mongo driver
     * @return \MongoDB\Client
     */
    public function getMongoClient()
    {
        return $this->link;
    }
}

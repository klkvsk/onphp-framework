<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-08-29
 */

/**
 * Ranged sharding
 * only works with integer sharding key
 */
class RangedSharding implements ShardingStrategy
{
    /** @var string */
    protected $tableName;
    /** @var string */
    protected $shardingKey;
    /**
     * @var Range[]
     */
    protected $shardRanges;

    public function __construct($tableName, $shardingKey, array $shardRanges)
    {
        $this->tableName   = $tableName;
        $this->shardingKey = $shardingKey;
        $this->shardRanges = $shardRanges;
    }

    /**
     * @param QuerySkeleton $query
     * @return array|int[]
     * @throws WrongArgumentException
     * @throws WrongStateException
     */
    public function getShardIdsByWhereClause(QuerySkeleton $query)
    {
        $chain = $query->getWhere();
        if ($chain === null) {
            return array_keys($this->shardRanges);
        }
        if ($chain instanceof LogicalChain) {
            $range = $this->extractRangesFromLogic($chain);
            if (__LOCAL_DEBUG__ && $range->getMin() === null && $range->getMax() === null) {
                //throw new WrongArgumentException('query does not include sharding key: ' . $query->toString());
            }
            $shards = $this->chooseShards($range);
            return $shards;
        }
        throw new WrongStateException();
    }

    protected function extractRangesFromLogic(LogicalChain $logicalChain) {
        $chain = $logicalChain->getChain();
        $logic = $logicalChain->getLogic();
        $min = $max = null;
        for ($i = 0; $i < $logicalChain->getSize(); $i++) {
            $expr = $chain[$i];
            if ($expr instanceof LogicalChain) {
                $subrange = $this->extractRangesFromLogic($expr);

                if ($subrange->getMax() === null) {
                    $max = null;
                } else if ($max === null) {
                    $max = $subrange->getMax();
                } else {
                    $max = min($max, $subrange->getMax());
                }

                if ($subrange->getMin() === null) {
                    $min = null;
                } else if ($min === null) {
                    $min = $subrange->getMin();
                } else {
                    $min = max($min, $subrange->getMin());
                }

            } else if ($expr instanceof BinaryExpression) {
                $left = $expr->getLeft();
                $right = $expr->getRight();
                $match = false;
                $value = null;
                if ($this->isShardingKey($left)) {
                    $value = $this->extractValue($right);
                    if ($value !== null) {
                        $match = true;
                    }
                }
                if (!$match && $logic[$i] == 'OR') {
                    return Range::create(null, null);
                }
                if ($match) {
                    switch ($expr->getLogic()) {
                        case '=':
                            $min = $max = $value;
                            break;

                        case '>':
                            if ($min === null) {
                                $min = $value;
                            } else {
                                $min = max($min, $value + 1);
                            }
                            break;

                        case '>=':
                            if ($min === null) {
                                $min = $value;
                            } else {
                                $min = max($min, $value);
                            }
                            break;

                        case '<':
                            if ($max === null) {
                                $max = $value;
                            } else {
                                $max = min($max, $value - 1);
                            }
                            break;

                        case '<=':
                            if ($max === null) {
                                $max = $value;
                            } else {
                                $max = min($max, $value);
                            }
                            break;

                        default:
                            throw new UnexpectedValueException('unknown operator ' . $expr->getLogic());
                    }
                }
            }
        }
        return Range::create($min, $max);
    }

    protected function extractValue($operand)
    {
        if ($operand instanceof DBValue) {
            return $operand->getValue();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getShardingKey()
    {
        return $this->shardingKey;
    }

    protected function isShardingKey($field)
    {
        if (is_string($field) && $field == $this->getShardingKey()) {
            return true;
        }
        if ($field instanceof DBField
            && $field->getField() == $this->getShardingKey()
            && $field->getTable() instanceof FromTable
            && $field->getTable()->getTable() == $this->getTableName()
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param InsertOrUpdateQuery $query
     * @return int|string
     * @throws MissingElementException
     * @throws WrongArgumentException
     */
    public function getShardIdByFieldValue(InsertOrUpdateQuery $query)
    {
        $fields = $query->getFields();
        if (!isset($fields[$this->shardingKey])) {
            throw new WrongArgumentException(
                'inserted/updated fields should contain sharding key, query: ' . $query->toString()
            );
        }

        return $this->chooseShard($fields[$this->shardingKey]);
    }

    /**
     * @param $shardId
     * @return mixed|Range
     */
    public function getRangeByShardId($shardId)
    {
        assert(isset($this->shardRanges[$shardId]));
        return $this->shardRanges[$shardId];
    }

    /**
     * @param $value
     * @return int|string
     * @throws MissingElementException
     */
    public function chooseShard($value) {
        foreach ($this->shardRanges as $shardId => $range) {
            if ($value >= $range->getMin() && $value <= $range->getMax()) {
                return $shardId;
            }
        }
        throw new MissingElementException('missing shard for ' . $this->shardingKey . '=' . $value);
    }

    public function chooseShards(Range $values) {
        $shards = [];
        foreach ($this->shardRanges as $shardId => $range) {
            if ($range->getMax() < $values->getMin())
                continue;
            if ($range->getMin() > $values->getMax() && $values->getMax() !== null)
                continue;

            $shards []= $shardId;
        }
        return $shards;
    }
}
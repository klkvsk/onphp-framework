<?php
/**
 * 
 * @author Соломонов Алексей <byorty@mail.ru>
 * @date 2015.04.17.04.15
 */

class PartitionCriteria implements Paginable {

    /**
     * @var PartitionDAO
     */
    private $dao   = null;

    /**
     * @var LogicalObject
     */
    private $logic = null;

    /**
     * @var OrderChain
     */
    private $order = null;

    private $from = null;
    private $equal = null;
    private $till = null;

    /**
     * @var int
     */
    private $limit	= null;

    /**
     * @var int
     */
    private $offset	= null;

    /**
     * @param $dao
     * @return static
     */
    public static function create($dao) {
        return new static($dao);
    }

    private function __construct($dao) {
        $this->dao = $dao;
        $this->logic = Expression::andBlock();
        $this->order = new OrderChain();
    }

    /**
     * @param LogicalObject $logic
     * @return static
     */
    public function add(LogicalObject $logic) {
        $this->logic->expAnd($logic);
        return $this;
    }

    /**
     * @return static
     **/
    public function addOrder(/* MapableObject */ $order) {
        if (!$order instanceof MappableObject)
            $order = new OrderBy($order);

        $this->order->add($order);

        return $this;
    }

    /**
     * @param null $from
     * @return static
     */
    public function setFrom($from) {
        $this->from = $from;
        return $this;
    }

    /**
     * @param null $equal
     * @return static
     */
    public function setEqual($equal) {
        $this->equal = $equal;
        return $this;
    }

    /**
     * @param null $till
     * @return $this
     */
    public function setTill($till) {
        $this->till = $till;
        return $this;
    }

    /**
     * @return static
     **/
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return static
     **/
    public function setOffset($offset) {
        $this->offset = $offset;
        return $this;
    }

    public function getList() {
        $result = [];
        /** @var SelectQuery[] $queries */
        $queries = $this->prepareQueries();
        foreach ($queries as $query) {
            try {
                $result = array_merge($result, $this->dao->getListByQuery($query));
            } catch (ObjectNotFoundException $e) {}
        }
        return $result;
    }

    /**
     * @return SelectQuery[]
     */
    private function prepareQueries() {
        /** @var PartitionType $type */
        $type = $this->dao->getPartitionType();
        $tablename = $this->dao->getTable();
        /** @var SelectQuery[] $criteries */
        $queries = [];
        if ($this->equal) {
            $queries[] = $this->createQuery($tablename, $this->equal, $type->next($this->equal));
        } else {
            if (!$this->from) {
                $this->from = $this->dao->getPartitionFrom();
            }
            if (!$this->till) {
                $this->till = $this->dao->getPartitionTill();
            }
            $range = $type->range($this->from, $this->till);
            for ($i = 0;$i < count($range);$i = $i + 2) {
                $next = isset($range[$i + 1]) ? $range[$i + 1] : $type->next($range[$i]);
                $queries[] = $this->createQuery($range[$i], $next);
            }
        }
        return $queries;
    }

    /**
     * @param $partitionValue
     * @return SelectQuery
     */
    private function createQuery($current, $next) {
        $this->dao->setPartitionPart(
            $this->dao->getPartitionType()->createTableName($current)
        );
        return Criteria::create($this->dao)
            ->setLogic(clone $this->logic)
            ->setOrder(clone $this->order)
            ->add(
                Expression::andBlock(
                    Expression::gtEq($this->dao->getPartitionField(), $current),
                    Expression::ltEq($this->dao->getPartitionField(), $next)
                )
            )
            ->toSelectQuery()
        ;
    }

    public function getResult() {
        $counts = [];
        $rows = [];
        /** @var SelectQuery[] $queries */
        $queries = $this->prepareQueries();
        foreach ($queries as $i => $query) {
            $count = clone $query;
            $counts[$i] =
                DBPool::getByDao($this->dao)->queryRow(
                    $count
                        ->dropFields()
                        ->dropOrder()
                        ->limit(null, null)
                        ->get(
                            SQLFunction::create('COUNT', '*')
                                ->setAlias('count')
                        )
                )['count'];
        }

        $hasLimit = $this->limit > 0;
        foreach ($counts as $i => $count) {
            if (!$count) {
                continue;
            }
            $queryOffset = 0;
            if ($this->offset) {
                for ($j = $this->offset >= $count ? $count : $this->offset;$j > 0;$j--) {
                    $queryOffset++;
                    $this->offset--;
                }
            }
            if (!$this->offset) {
                if ($hasLimit) {
                    if (!$this->limit) {
                        break;
                    }
                    $queryLimit = 0;
                    $offsetCount = $count - $queryOffset;
                    if ($this->limit) {
                        for ($j = $this->limit >= $offsetCount ? $offsetCount : $this->limit;$j > 0;$j--) {
                            $queryLimit++;
                            $this->limit--;
                        }
                    }
                    $queries[$i]->limit($queryLimit, $queryOffset);
                    try {
                        $rows = array_merge($rows, $this->dao->getListByQuery($queries[$i]));
                    } catch (ObjectNotFoundException $e) {}
                } else {
                    try {
                        $rows = array_merge($rows, $this->dao->getListByQuery($queries[$i]));
                    } catch (ObjectNotFoundException $e) {}
                }
            }
        }
        return QueryResult::create()
            ->setCount(array_reduce($counts, function($count, $item) {
                $count += $item;
                return $count;
            }))
            ->setList($rows)
        ;
    }
}

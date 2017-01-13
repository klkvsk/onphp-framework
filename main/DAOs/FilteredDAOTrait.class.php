<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-01-12
 */
trait FilteredDAOTrait
{
    /** @var LogicalObject */
    protected $filterLogic;

    /**
     * @return LogicalObject
     */
    public function getFilterLogic() {
        return $this->filterLogic;
    }

    /**
     * @param LogicalObject $logic
     * @return $this
     */
    public function setFilterLogic(LogicalObject $logic) {
        $this->filterLogic = $logic;
        return $this;
    }

    /**
     * @return $this
     */
    public function dropFilterLogic() {
        $this->filterLogic = null;
        return $this;
    }

    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    public function filterSelectQuery(SelectQuery $query) {
        $logic = $this->getFilterLogic();

        if (!$logic) {
            return $query;
        }

        if ($logic instanceof MappableObject && $this instanceof ProtoDAO) {
            $logic = $logic->toMapped($this, $query);
        }

        $query->andWhere($logic);

        return $query;
    }

    public function makeSelectHead() {
        if ($this instanceof GenericDAO) {
            return $this->filterSelectQuery(parent::makeSelectHead());
        } else {
            throw new BadMethodCallException();
        }
    }

    public function makeTotalCountQuery() {
        if ($this instanceof GenericDAO) {
            return $this->filterSelectQuery(parent::makeTotalCountQuery());
        } else {
            throw new BadMethodCallException();
        }
    }

}
<?php

/**
 * Created by PhpStorm.
 * User: byorty
 * Date: 06.06.14
 * Time: 11:10
 */
class TreeDaoWorker extends CommonDaoWorker {

    const
        SUFFIX_ITEM = '_item_',
        SUFFIX_CUSTOM = '_custom_';

    private $suffix;

    public function getById($id, $expires = Cache::EXPIRES_MEDIUM) {
        return $this->getByQuery($this->makeIdKey($id, false), $expires);
    }

    protected function makeIdKey($id, $toString = true) {
        /** @var SelectQuery $query */
        $query =
            $this->dao->
                makeSelectHead()->
                andWhere(
                    Expression::eq(
                        DBField::create(
                            $this->dao->getIdName(),
                            $this->dao->getTable()
                        ),
                        $id
                    )
                );
        if ($toString) {
            return $this->makeQueryKey($query, self::SUFFIX_ITEM);
        } else {
            return $query;
        }
    }

    /**
     * @return $this
     */
    private function setSuffixItem() {
        $this->suffix = self::SUFFIX_ITEM;
        return $this;
    }

    /**
     * @return $this
     */
    private function setSuffixList() {
        $this->suffix = self::SUFFIX_LIST;
        return $this;
    }

    /**
     * @return $this
     */
    private function setSuffixCustom() {
        $this->suffix = self::SUFFIX_CUSTOM;
        return $this;
    }

    protected function getSuffixQuery() {
        return $this->suffix;
    }

    public function getByQuery(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixItem();
        return parent::getByQuery($query, $expires);
    }

    public function getCustom(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixCustom();
        return parent::getCustom($query, $expires);
    }

    public function getListByQuery(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixList();
        return parent::getListByQuery($query, $expires);
    }

    public function getCustomList(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixList();
        return parent::getCustomList($query, $expires);
    }

    public function getCustomRowList(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixList();
        return parent::getCustomRowList($query, $expires);
    }

    public function getQueryResult(SelectQuery $query, $expires = Cache::DO_NOT_CACHE) {
        $this->setSuffixList();
        return parent::getQueryResult($query, $expires);
    }

    public function uncacheByIds($ids) {
        $success = true;
        foreach ($ids as $id) {
            $result = Cache::me()
                ->mark($this->className)
                ->delete($this->makeIdKey($id));
            if (!$result) {
                $success = $result;
            }
        }

        return $success;
    }

    public function uncacheListByQuery(SelectQuery $query) {
        $this->setSuffixList();
        return parent::uncacheByQuery(
            $this->getClearQuery($query)
        );
    }

    protected function cacheByQuery(
        SelectQuery $query,
        /* Identifiable */
        $object,
        $expires = Cache::DO_NOT_CACHE
    ) {
        if ($expires !== Cache::DO_NOT_CACHE) {

            if (self::SUFFIX_ITEM == $this->getSuffixQuery()) {

                $idKey = $this->makeIdKey($object->getId());
                $queryKey = $this->makeQueryKey($query, $this->getSuffixQuery());

                if ($idKey != $queryKey) {
                    Cache::me()
                        ->mark($this->className)
                        ->set(
                            $queryKey,
                            CacheLink::create()
                                ->setKey($idKey),
                            $expires
                        );
                }

                Cache::me()
                    ->mark($this->className)
                    ->set(
                        $idKey,
                        $object,
                        $expires
                    );
            } else if (self::SUFFIX_LIST == $this->getSuffixQuery() || self::SUFFIX_RESULT == $this->getSuffixQuery()) {
                $isResult = $object instanceof QueryResult;
                /** @var CacheListLink $link */
                $link = CacheListLink::create();

                if ($isResult) {
                    $link
                        ->setResult(true)
                        ->setCount($object->getCount())
                    ;
                    $items = $object->getList();
                } else {
                    $items = $object;
                }
                foreach ($items as $i => $item) {
                    if ($item instanceof Identifiable) {
                        $idKey = $this->makeIdKey($item->getId());

                        Cache::me()
                            ->mark($this->className)
                            ->set(
                                $idKey,
                                $item,
                                $expires
                            );

                        $link->setKey($item->getId(), $idKey);
                    } else {
                        $link->setValue($i, $item);
                    }
                }

                if ($this->cacheAsRoot($query)) {
                    /** @var $rootLink CacheListLink */
                    $rootLink = $this->getRootLink($query);
                    $rootLink->setKey($this->getBranchLinkKey($query), $link);

                    Cache::me()->mark($this->className)->
                        set(
                            $rootLink->getId(),
                            $rootLink,
                            $expires
                        );
                } else {
                    parent::cacheByQuery($query, $link, $expires);
                }
            } else {
                parent::cacheByQuery($query, $object, $expires);
            }
        }

        return $object;
    }

    private function cacheAsRoot(SelectQuery $query) {
        return $query->getLimit() || $query->getOffset() || $query->getOrder()->getCount();
    }

    /**
     * @param SelectQuery $query
     * @return CacheListLink
     */
    protected function getRootLink(SelectQuery $query) {
        $clearQuery = $this->getClearQuery($query);

        $key = $this->makeQueryKey(
            $clearQuery,
            $this->getSuffixQuery()
        );

        $link = Cache::me()
            ->mark($this->className)
            ->get($key);

        $link = $link instanceof CacheListLink ? $link : CacheListLink::create();
        $link
            ->setId($key)
            ->hasSubLinksOn();

        return $link;
    }

    protected function getClearQuery(SelectQuery $query) {
        $clearQuery = clone $query;
        $clearQuery
            ->dropLimit()
            ->dropOrder()
        ;
        return $clearQuery;
    }

    private function getBranchLinkKey(SelectQuery $query) {
        return implode('_', [
            $query->getLimit(),
            $query->getOffset(),
            md5($query->getOrder()->toDialectString(DBPool::getByDao($this->dao)->getDialect()))
        ]);
    }

    protected function getCachedByQuery(SelectQuery $query) {
        if ($this->cacheAsRoot($query)) {
            $object = parent::getCachedByQuery($this->getClearQuery($query));
        } else {
            $object = parent::getCachedByQuery($query);
        }

        $result = null;
        if ($object instanceof CacheLink) {
            $result = Cache::me()->get($object->getKey());
        } else if ($object instanceof CacheListLink) {
            if ($object->hasSubLinks()) {
                $subLink = $object->getSubLink($this->getBranchLinkKey($query));
                if ($subLink) {
                    $object = $subLink;
                    $keys = $subLink->getKeys();
                    if (is_array($keys) && count($keys)) {
                        $result = Cache::me()->getList($keys);
                    }
                }
            } else {
                $keys = $object->getKeys();
                if ($keys) {
                    $result = Cache::me()->getList($keys);
                } else {
                    $result = $object->getValues();
                }
            }

            if ($result && $keys) {
                foreach ($keys as $id => $key) {
                    if (!$result[$key]) {
                        try {
                            $item = $this->dao->getById($id);
                            $result[$key] = $item;
                            Cache::me()
                                ->mark($this->className)
                                ->add(
                                    $this->makeIdKey($id),
                                    $item,
                                    Cache::EXPIRES_MEDIUM
                                );
                        } catch (ObjectNotFoundException $e) {
                            unset($result[$key]);
                        }
                    }
                }

                $result = array_values($result);
                if ($object instanceof CacheListLink && $object->isResult()) {
                    $result = QueryResult::create()
                        ->setList($result)
                        ->setCount($object->getCount())
                    ;
                }
            }
        } else {
            $result = $object;
        }

        return $result;
    }
} 
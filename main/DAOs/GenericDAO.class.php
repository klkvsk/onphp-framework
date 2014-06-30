<?php
/***************************************************************************
 *   Copyright (C) 2005-2008 by Konstantin V. Arkhipov                     *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * Basis of all DAO's.
	 *
	 * @ingroup DAOs
	**/
	abstract class GenericDAO extends Singleton implements BaseDAO
	{
		private $identityMap	= array();

        private $triggersAllowed = true;

		abstract public function getTable();
		abstract public function getObjectName();

		public function makeObject($array, $prefix = null)
		{
			if (
				isset(
					$this->identityMap[
						$array[$idName = $prefix.$this->getIdName()]
					]
				)
			) {
				$this->getProtoClass()->skipObjectPrefetching(
					$this->identityMap[$array[$idName]]
				);

				return $this->identityMap[$array[$idName]];
			}

			return
				$this->completeObject(
					$this->makeOnlyObject($array, $prefix)
				);
		}

		public function makeOnlyObject($array, $prefix = null)
		{
			// adding incomplete object to identity map
			// solves case with circular-dependent objects
			return $this->addObjectToMap(
				$this->getProtoClass()->makeOnlyObject(
					$this->getObjectName(), $array, $prefix
				)
			);
		}

		public function completeObject(Identifiable $object)
		{
			return $this->getProtoClass()->completeObject(
				// same purpose as in makeOnlyObject,
				// but for objects retrieved from cache
				$this->addObjectToMap($object)
			);
		}

		/**
		 * Returns link name which is used to get actual DB-link from DBPool,
		 * returning null by default for single-source projects.
		 *
		 * @see DBPool
		**/
		public function getLinkName()
		{
			return null;
		}

		public function getIdName()
		{
			return 'id';
		}

		public function getSequence()
		{
			return $this->getTable().'_id';
		}

		/**
		 * @return AbstractProtoClass
		**/
		public function getProtoClass()
		{
			static $protos = array();

			if (!isset($protos[$className = $this->getObjectName()]))
				$protos[$className] = call_user_func(array($className, 'proto'));

			return $protos[$className];
		}

		public function getMapping()
		{
			return $this->getProtoClass()->getMapping();
		}

		public function getFields()
		{
			static $fields = array();

			$className = $this->getObjectName();

			if (!isset($fields[$className])) {
				$fields[$className] = array_values($this->getMapping());
			}

			return $fields[$className];
		}

		/**
		 * @return SelectQuery
		**/
		public function makeSelectHead()
		{
			static $selectHead = array();

			if (!isset($selectHead[$className = $this->getObjectName()])) {
				$table = $this->getTable();

				$object =
					OSQL::select()->
					from($table);

				foreach ($this->getFields() as $field)
					$object->get(new DBField($field, $table));

				$selectHead[$className] = $object;
			}

			return clone $selectHead[$className];
		}

		/**
		 * @return SelectQuery
		**/
		public function makeTotalCountQuery()
		{
			return
				OSQL::select()->
				get(
					SQLFunction::create('count', DBValue::create('*'))
				)->
				from($this->getTable());
		}

		/// boring delegates
		//@{
		public function getById($id, $expires = Cache::EXPIRES_FOREVER)
		{
			Assert::isScalar($id);
			Assert::isNotEmpty($id);

			if (isset($this->identityMap[$id]))
				return $this->identityMap[$id];

			return $this->addObjectToMap(
				Cache::worker($this)->getById($id, $expires)
			);
		}

		public function getByLogic(
			LogicalObject $logic, $expires = Cache::DO_NOT_CACHE
		)
		{
			return $this->addObjectToMap(
				Cache::worker($this)->getByLogic($logic, $expires)
			);
		}

		public function getByQuery(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return $this->addObjectToMap(
				Cache::worker($this)->getByQuery($query, $expires)
			);
		}

		public function getCustom(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return Cache::worker($this)->getCustom($query, $expires);
		}

		public function getListByIds(
			array $ids, $expires = Cache::EXPIRES_MEDIUM
		)
		{
			$mapped = $remain = array();

			foreach ($ids as $id) {
				if (isset($this->identityMap[$id])) {
					$mapped[] = $this->identityMap[$id];
				} else {
					$remain[] = $id;
				}
			}

			if ($remain) {
				$list = $this->addObjectListToMap(
					Cache::worker($this)->getListByIds($remain, $expires)
				);

				$mapped = array_merge($mapped, $list);
			}

			return ArrayUtils::regularizeList($ids, $mapped);
		}

		public function getListByQuery(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return $this->addObjectListToMap(
				Cache::worker($this)->getListByQuery($query, $expires)
			);
		}

		public function getListByLogic(
			LogicalObject $logic, $expires = Cache::DO_NOT_CACHE
		)
		{
			return $this->addObjectListToMap(
				Cache::worker($this)->getListByLogic($logic, $expires)
			);
		}

		public function getPlainList($expires = Cache::EXPIRES_MEDIUM)
		{
			return $this->addObjectListToMap(
				Cache::worker($this)->getPlainList($expires)
			);
		}

		public function getTotalCount($expires = Cache::DO_NOT_CACHE)
		{
			return Cache::worker($this)->getTotalCount($expires);
		}

		public function getCustomList(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return Cache::worker($this)->getCustomList($query, $expires);
		}

		public function getCustomRowList(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return Cache::worker($this)->getCustomRowList($query, $expires);
		}

		public function getQueryResult(
			SelectQuery $query, $expires = Cache::DO_NOT_CACHE
		)
		{
			return Cache::worker($this)->getQueryResult($query, $expires);
		}

		public function drop(Identifiable $object)
		{
			$this->checkObjectType($object);

			return $this->dropById($object->getId());
		}

		public function dropById($id)
		{
            call_user_func($this->prepareTrigger($id, 'onBeforeDrop'));
            $after = $this->prepareTrigger($id, 'onAfterDrop');

			unset($this->identityMap[$id]);

			$count = Cache::worker($this)->dropById($id);

            call_user_func($after);

			if (1 != $count)
				throw new WrongStateException('no object were dropped');

			return $count;
		}

		public function dropByIds(array $ids)
		{
            call_user_func($this->prepareTrigger($ids, 'onBeforeDrop'));
            $after = $this->prepareTrigger($ids, 'onAfterDrop');

			foreach ($ids as $id)
				unset($this->identityMap[$id]);

			$count = Cache::worker($this)->dropByIds($ids);

            call_user_func($after);

			if ($count != count($ids))
				throw new WrongStateException('not all objects were dropped');

			return $count;
		}

		public function uncacheById($id)
		{
			unset($this->identityMap[$id]);

			return Cache::worker($this)->uncacheById($id);
		}

		public function uncacheByIds($ids)
		{
			foreach ($ids as $id)
				unset($this->identityMap[$id]);

			return Cache::worker($this)->uncacheByIds($ids);
		}

		public function uncacheLists()
		{
			$this->dropIdentityMap();

			return Cache::worker($this)->uncacheLists();
		}

        public function uncacheByQuery(SelectQuery $query) {
            try {
                $item = Cache::worker($this)->getByQuery($query);
                if ($item instanceof Identifiable && isset($this->identityMap[$item->getId()])) {
                    unset($this->identityMap[$item->getId()]);
                }
            } catch (ObjectNotFoundException $e) {}
            return Cache::worker($this)->uncacheByQuery($query);
        }

        public function uncacheCustomByQuery(SelectQuery $query) {
            return Cache::worker($this)->uncacheCustomByQuery($query);
        }

        public function uncacheByLogic(LogicalObject $logic) {
            try {
                $item = Cache::worker($this)->getByLogic($logic);
                if ($item instanceof Identifiable && isset($this->identityMap[$item->getId()])) {
                    unset($this->identityMap[$item->getId()]);
                }
            } catch (ObjectNotFoundException $e) {}
            return Cache::worker($this)->uncacheByLogic($logic);
        }

        public function uncacheListByQuery(SelectQuery $query) {
            return Cache::worker($this)->uncacheListByQuery($query);
        }

        public function uncacheItems() {

            $this->dropIdentityMap();

            return Cache::worker($this)->uncacheItems();
        }
        //@}

		/**
		 * @return GenericDAO
		**/
		public function dropIdentityMap()
		{
			$this->identityMap = array();

			return $this;
		}

		public function dropObjectIdentityMapById($id)
		{
			unset($this->identityMap[$id]);

			return $this;
		}

        public function disableTriggers() {
            $this->triggersAllowed = false;
            return $this;
        }

        public function enableTriggers() {
            $this->triggersAllowed = true;
            return $this;
        }

		protected function inject(
			InsertOrUpdateQuery $query,
			Identifiable $object
		)
		{
			$this->checkObjectType($object);

            $this->runTrigger($object, 'onBeforeSave');

            return $this->doInject(
				$this->setQueryFields(
					$query->setTable($this->getTable()), $object
				),
				$object
			);
		}

		protected function doInject(
			InsertOrUpdateQuery $query,
			Identifiable $object
		)
		{
			$db = DBPool::getByDao($this);

			if (!$db->isQueueActive()) {
				$count = $db->queryCount($query);

				$this->uncacheById($object->getId());

				if ($count !== 1)
					throw new WrongStateException(
						$count.' rows affected: racy or insane inject happened: '
						.$query->toDialectString($db->getDialect())
					);
			} else {
				$db->queryNull($query);

				$this->uncacheById($object->getId());
			}

			// clean out Identifier, if any
			$result = $this->addObjectToMap($object->setId($object->getId()));

            $this->runTrigger($object, 'onAfterSave');

            return $result;
		}

		/* void */ protected function checkObjectType(Identifiable $object)
		{
			Assert::isSame(
				get_class($object),
				$this->getObjectName(),
				'strange object given, i can not inject it'
			);
		}

		private function addObjectToMap(Identifiable $object)
		{
			return $this->identityMap[$object->getId()] = $object;
		}

		private function addObjectListToMap($list)
		{
			foreach ($list as $object)
				$this->identityMap[$object->getId()] = $object;

			return $list;
		}

        protected final function runTrigger($input, $triggerName) {
            call_user_func($this->prepareTrigger($input, $triggerName));

            return $this;
        }

        protected final function prepareTrigger($input, $triggerName) {
            if(
                !$this->triggersAllowed ||
                !in_array($triggerName, class_implements($objName = $this->getObjectName()))
                ) {
                return (function(){ });
            }

            $self = $this;
            $check = function($obj) use ($objName, $self) {
                if(!($obj instanceof $objName)) {
                    $obj = $self->getById($obj);
                }
                return $obj;
            };

            if(is_array($input)) {
                $input = array_map($check, $input);
            } else {
                $input = array($check($input));
            }

            return function() use ($input, $triggerName) {
                foreach($input as $obj) {
                    call_user_func(array($obj, $triggerName));
                }
            };
        }
	}
?>
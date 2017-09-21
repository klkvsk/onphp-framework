<?php
/***************************************************************************
 *   Copyright (C) 2004-2008 by Konstantin V. Arkhipov                     *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * @ingroup OSQL
	**/
	abstract class QuerySkeleton extends QueryIdentification
	{
		protected $where		= null;
		protected $aliases		= array();
		protected $returning 	= array();

        /**
         * @return LogicalChain|null
         */
		public function getWhere()
		{
			return $this->where;
		}

        /**
         * @param LogicalObject $exp
         * @param $logic
         * @return $this
         * @throws WrongArgumentException
         */
		public function where(LogicalObject $exp, $logic = null)
		{
			if (!$this->where) {
			    $this->where = new LogicalChain();
            }
            switch ($logic) {
                case 'OR':
                    $this->where->expOr($exp);
                    break;

                case 'AND':
                case '':
                case null:
                    $this->where->expAnd($exp);
                    break;

                default:
                    throw new WrongArgumentException($logic);
            }
			return $this;
		}

        /**
         * @param LogicalObject $exp
         * @return $this
         * @throws WrongArgumentException
         */
		public function andWhere(LogicalObject $exp)
		{
			return $this->where($exp, 'AND');
		}

        /**
         * @param LogicalObject $exp
         * @return $this
         * @throws WrongArgumentException
         */
		public function orWhere(LogicalObject $exp)
		{
			return $this->where($exp, 'OR');
		}
		
		/**
		 * @return $this
		**/
		public function returning($field, $alias = null)
		{
			$this->returning[] =
				$this->resolveSelectField(
					$field,
					$alias,
					$this->table
				);
			
			if ($alias = $this->resolveAliasByField($field, $alias)) {
				$this->aliases[$alias] = true;
			}
			
			return $this;
		}
		
		/**
		 * @return $this
		**/
		public function dropReturning()
		{
			$this->returning = array();
			
			return $this;
		}

        /**
         * @param Dialect $dialect
         * @return string
         */
		public function toDialectString(Dialect $dialect)
		{
			return $this->getWhere()
                ? ' WHERE ' . $this->getWhere()->toDialectString($dialect)
                : '';
		}
		
		protected function resolveSelectField($field, $alias, $table)
		{
			if (is_object($field)) {
				if (
					($field instanceof DBField)
					&& ($field->getTable() === null)
				) {
					$result = new SelectField(
						$field->setTable($table),
						$alias
					);
				} elseif ($field instanceof SelectQuery) {
					$result = $field;
				} elseif ($field instanceof DialectString) {
					$result = new SelectField($field, $alias);
				} else
					throw new WrongArgumentException('unknown field type');
				
				return $result;
			} elseif (false !== strpos($field, '*'))
				throw new WrongArgumentException(
					'do not fsck with us: specify fields explicitly'
				);
			elseif (false !== strpos($field, '.'))
				throw new WrongArgumentException(
					'forget about dot: use DBField'
				);
			else
				$fieldName = $field;
			
			$result = new SelectField(
				new DBField($fieldName, $table), $alias
			);
			
			return $result;
		}
		
		protected function resolveAliasByField($field, $alias)
		{
			if (is_object($field)) {
				if (
					($field instanceof DBField)
					&& ($field->getTable() === null)
				) {
					return null;
				}
				
				if (
					$field instanceof SelectQuery
					|| ($field instanceof DialectString	&& $field instanceof Aliased)
				) {
					return $field->getAlias();
				}
			}
			
			return $alias;
		}

        /**
         * @param Dialect $dialect
         * @return $this
         * @throws UnimplementedFeatureException
         */
		protected function checkReturning(Dialect $dialect)
		{
			if (
				$this->returning
				&& !$dialect->hasReturning()
			) {
				throw new UnimplementedFeatureException();
			}
			
			return $this;
		}
		
		protected function toDialectStringField($field, Dialect $dialect)
		{
			if ($field instanceof SelectQuery) {
				Assert::isTrue(
					null !== $alias = $field->getName(),
					'can not use SelectQuery to table without name as get field: '
					.$field->toDialectString(ImaginaryDialect::me())
				);
				
				return
					"({$field->toDialectString($dialect)}) AS ".
					$dialect->quoteField($alias);
			} else
				return $field->toDialectString($dialect);
		}
		
		protected function toDialectStringReturning(Dialect $dialect)
		{
			$fields = array();
			
			foreach ($this->returning as $field)
				$fields[] = $this->toDialectStringField($field, $dialect);
			
			return implode(', ', $fields);
		}
	}
?>
<?php
/***************************************************************************
 *   Copyright (C) 2007 by Konstantin V. Arkhipov                          *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	/**
	 * @ingroup Projections
	**/
	final class ClassProjection implements ObjectProjection
	{
		private $className	= null;
		
		/**
		 * @return ClassProjection
		**/
		public static function create($class)
		{
			return new self($class);
		}
		
		public function __construct($class)
		{
			Assert::isTrue(
				ClassUtils::isInstanceOf($class, 'Prototyped')
			);
			
			if (is_object($class))
				$this->className = get_class($class);
			else
				$this->className = $class;
		}
		
		/**
		 * @return SelectQuery
		**/
		public function process(Criteria $criteria, JoinCapableQuery $query)
		{
			$dao = call_user_func(array($this->className, 'dao'));
			
			foreach ($dao->getFields() as $field)
				$query->get($field);
			
			return $query;
		}
	}
?>
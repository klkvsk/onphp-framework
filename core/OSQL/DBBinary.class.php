<?php
/***************************************************************************
 *   Copyright (C) 2007 by Konstantin V. Arkhipov                          *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	/**
	 * Container for passing binary values into OSQL queries.
	 * 
	 * @ingroup OSQL
	**/
	final class DBBinary implements DialectString
	{
		private $value = null;
		
		/**
		 * @return DBValue
		**/
		public static function create($value)
		{
			return new self($value);
		}
		
		public function __construct($value)
		{
			$this->value = $value;
		}
		
		public function getValue()
		{
			return $this->value;
		}
		
		public function toDialectString(Dialect $dialect)
		{
			return "'".$dialect->quoteBinary($this->value)."'";
		}
	}
?>
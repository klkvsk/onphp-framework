<?php
/***************************************************************************
 *   Copyright (C) 2005-2007 by Konstantin V. Arkhipov                     *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * Transaction's basis.
	 * 
	 * @ingroup Transaction
	**/
	abstract class BaseTransaction
	{
	    /** @var DBInterface|null  */
		protected $db		= null;
		/** @var IsolationLevel|null  */
		protected $isoLevel	= null;
		/** @var AccessMode|null  */
		protected $mode		= null;
		
		abstract public function flush();
		
		public function __construct(DBInterface $db)
		{
			$this->db = $db;
		}

        /**
         * @param DBInterface $db
         * @return $this
         */
		public function setDB(DBInterface $db)
		{
			$this->db = $db;
			
			return $this;
		}
		
		/**
		 * @return DBInterface
		**/
		public function getDB()
		{
			return $this->db;
		}

        /**
         * @param IsolationLevel $level
         * @return $this
         */
		public function setIsolationLevel(IsolationLevel $level)
		{
			$this->isoLevel = $level;
			
			return $this;
		}

        /**
         * @param AccessMode $mode
         * @return $this
         */
		public function setAccessMode(AccessMode $mode)
		{
			$this->mode = $mode;
			
			return $this;
		}

        /**
         * @return string
         */
		protected function getBeginString()
		{
			$begin = 'start transaction';
			
			if ($this->isoLevel)
				$begin .= ' '.$this->isoLevel->toString();
			
			if ($this->mode)
				$begin .= ' '.$this->mode->toString();
			
			return $begin.";\n";
		}
	}
?>
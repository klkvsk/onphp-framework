<?php
/****************************************************************************
 *   Copyright (C) 2007 by Konstantin V. Arkhipov                           *
 *                                                                          *
 *   This program is free software; you can redistribute it and/or modify   *
 *   it under the terms of the GNU Lesser General Public License as         *
 *   published by the Free Software Foundation; either version 3 of the     *
 *   License, or (at your option) any later version.                        *
 *                                                                          *
 ****************************************************************************/

	/**
	 * @ingroup Exceptions
	 * @ingroup Module
	**/
	final class ClassNotFoundException extends BaseException {

	    /** @var string */
	    protected $className;

        public static function create($className)
        {
            return new static('class not found: ' . $className, 0, null, $className);
	    }

        public function __construct($message = "", $code = 0, Throwable $previous = null, $className = null)
        {
            parent::__construct($message, $code, $previous);
            $this->className = $className;
        }

        /**
         * @return string
         */
        public function getClassName()
        {
            return $this->className;
        }

    }
?>
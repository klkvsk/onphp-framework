<?php
/***************************************************************************
 *   Copyright (C) 2007 by Ivan Y. Khvostishkov                            *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	final class DTOToScopeConverter extends DTOConverter
	{
		protected function createResult()
		{
			return array();
		}
		
		protected function alterResult($result)
		{
			return $result;
		}
		
		protected function preserveTypeLoss($value, DTOProto $childProto)
		{
			// NOTE: type loss here
			return $this;
		}
		
		protected function saveToResult(
			$value, BasePrimitive $primitive, &$result
		)
		{
			Assert::isTrue(!is_object($value));
			
			$result[$primitive->getName()] =  $value;
			
			return $this;
		}
	}
?>
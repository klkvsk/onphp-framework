<?php
/***************************************************************************
 *   Copyright (C) 2009 by Sergey S. Sergeev                               *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * @ingroup Types
	 * @see http://www.postgresql.org/docs/8.3/interactive/json.html
	**/
	class JsonType extends StringType
	{
		public function getPrimitiveName()
		{
			return 'json';
		}

		public function toColumnType()
		{
			return 'DataType::create(DataType::JSON)';
		}

        public function toGetter(
            MetaClass $class,
            MetaClassProperty $property,
            MetaClassProperty $holder = null
        )
        {
            if ($holder)
                $name = $holder->getName().'->get'.ucfirst($property->getName()).'()';
            else
                $name = $property->getName();

            $methodName = 'get'.ucfirst($property->getName());
            $decodedMethodName = 'getDecode'.ucfirst($property->getName());

            return <<<EOT

public function {$methodName}()
{
	return \$this->{$name};
}

public function {$decodedMethodName}()
{
	return json_decode(\$this->{$methodName}());
}

EOT;
        }

        public function toSetter(
            MetaClass $class,
            MetaClassProperty $property,
            MetaClassProperty $holder = null
        )
        {
            $name = $property->getName();
            $methodName = 'set'.ucfirst($name);
            $encodeMethodName = 'setEncode'.ucfirst($name);

            return <<<EOT

/**
 * @return {$class->getName()}
**/
public function {$methodName}(\${$name})
{
	\$this->{$name} = \${$name};

	return \$this;
}

/**
 * @return {$class->getName()}
**/
public function {$encodeMethodName}(\${$name})
{
	return \$this->{$methodName}(json_encode(\${$name}));
}

EOT;
        }
	}
<?php
/**
 *
 * @author Михаил Кулаковский <m@klkvsk.ru>
 * @date 14.01.2015
 */

class ArrayOfEnumsType extends ArrayOfIntegersType {

	/** @var MetaClass */
	protected $enumClass;

	function __construct($type, array $parameters) {
		Assert::isNotEmptyArray($parameters, 'enum class name is not provided');
		list($enumerationClassName) = $parameters;

		$this->enumClass = MetaConfiguration::me()->getClassByName($enumerationClassName);

		Assert::isTrue(
			$this->enumClass->getPattern() instanceof EnumClassPattern,
			'only enum classes can be provided for ArrayOfEnums type'
		);
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

		$methodName = 'get'.ucfirst($property->getName()).'List';

		return parent::toGetter($class, $property, $holder) . <<<EOT

/**
 * @return {$this->enumClass->getName()}[]
**/
public function {$methodName}()
{
	return array_map(array('{$this->enumClass->getName()}', 'create'), \$this->{$name});
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
		$methodName = 'set'.ucfirst($name).'List';

		$default = $property->isRequired() ? '' : ' = null';

		if ($holder) {
			Assert::isUnreachable();
		} else {
			return parent::toSetter($class, $property, $holder) . <<<EOT

/**
 * @param \${$name} {$this->enumClass->getName()}[]
 * @return {$class->getName()}
**/
public function {$methodName}(array \${$name}{$default})
{
	\$this->{$name} = ArrayUtils::getIdsArray(\${$name});

	return \$this;
}

EOT;
		}

		Assert::isUnreachable();
	}

}
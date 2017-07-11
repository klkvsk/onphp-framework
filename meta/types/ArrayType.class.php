<?php
/**
 *
 * @author Михаил Кулаковский <m@klkvsk.ru>
 * @date 2012.08.02
 */
class ArrayType extends BasePropertyType {

	public function getPrimitiveName() {
		return 'set';
	}

	public function getDeclaration() {
		return 'array()';
	}

	public function isMeasurable() {
		return true;
	}

	public function toColumnType() {
		throw new UnimplementedFeatureException('ArrayType should not be used within SQL databases');
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

		return <<<EOT

/**
 * @return {$this->getHint()}
 */
public function {$methodName}()
{
	return \$this->{$name};
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

		$nullArg = $property->isRequired() ? '' : ' = null';
		$nullParam = $property->isRequired() ? '' : '|null';

		if ($holder) {
			Assert::isUnreachable();
		} else {
			return <<<EOT

/**
 * @param {$this->getHint()}{$nullParam} \${$name}
 * @return \$this
 */
public function {$methodName}(array \${$name}{$nullArg})
{
	\$this->{$name} = \${$name};

	return \$this;
}

EOT;
		}

		Assert::isUnreachable();
	}

    public function getHint()
    {
        return 'array';
    }

}

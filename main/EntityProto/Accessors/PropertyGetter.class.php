<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-06-21
 */
class PropertyGetter extends PrototypedGetter
{
    public function get($name)
    {
        if (!isset($this->mapping[$name]))
            throw new WrongArgumentException(
                "knows nothing about property '{$name}'"
            );

        $primitive = $this->mapping[$name];

        $key = $primitive->getName();

        return
            isset($this->object->{$key})
                ? $this->object->{$key}
                : null;
    }
}
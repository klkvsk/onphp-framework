<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-02-15
 */
interface LogicalOperandProvider
{
    public function toOperand($value);

}
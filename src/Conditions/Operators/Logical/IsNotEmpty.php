<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsNotEmpty operator for checking if a value is not empty.
 * 
 * Opposite of IsEmpty - returns true if the value is not empty.
 * The second parameter is ignored for this operator.
 */
class IsNotEmpty implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return !empty($value1);
    }
}
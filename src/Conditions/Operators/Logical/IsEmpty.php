<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsEmpty operator for checking if a value is empty.
 * 
 * Uses PHP's empty() function logic:
 * - null, false, 0, '0', '', [] are considered empty
 * - The second parameter is ignored for this operator
 */
class IsEmpty implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return empty($value1);
    }
}
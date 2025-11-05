<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsNotNull operator for checking if a value is not null.
 * 
 * Uses strict null comparison (!== null).
 * The second parameter is ignored for this operator.
 */
class IsNotNull implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 !== null;
    }
}
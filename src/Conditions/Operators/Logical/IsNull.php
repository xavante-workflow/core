<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsNull operator for checking if a value is null.
 * 
 * Uses strict null comparison (=== null).
 * The second parameter is ignored for this operator.
 */
class IsNull implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 === null;
    }
}
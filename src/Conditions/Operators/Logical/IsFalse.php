<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsFalse operator for checking if a value evaluates to false.
 * 
 * Uses strict boolean comparison (=== false).
 * The second parameter is ignored for this operator.
 */
class IsFalse implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 === false;
    }
}
<?php

namespace Xavante\Conditions\Operators\Logical;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsTrue operator for checking if a value evaluates to true.
 * 
 * Uses strict boolean comparison (=== true).
 * The second parameter is ignored for this operator.
 */
class IsTrue implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 === true;
    }
}
<?php

namespace Xavante\Conditions\Operators\Comparison;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * NotEquals operator for inequality comparison.
 * 
 * Uses strict comparison (!==) to ensure type safety.
 * Returns true when values or types differ.
 */
class NotEquals implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 !== $value2;
    }
}
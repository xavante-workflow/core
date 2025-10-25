<?php

namespace Xavante\Conditions\Operators\Comparison;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * Equals operator for exact value comparison.
 * 
 * Uses strict comparison (===) to ensure type safety.
 * Returns true only when both value and type are identical.
 */
class Equals implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 === $value2;
    }
}
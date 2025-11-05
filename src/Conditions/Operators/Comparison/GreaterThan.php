<?php

namespace Xavante\Conditions\Operators\Comparison;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * GreaterThan operator for numeric and comparable value comparison.
 * 
 * Supports numeric values, strings (lexicographic), and DateTime objects.
 * Returns true if the first value is greater than the second.
 */
class GreaterThan implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        // Handle null values
        if ($value1 === null || $value2 === null) {
            return false;
        }

        // DateTime comparison
        if ($value1 instanceof \DateTime && $value2 instanceof \DateTime) {
            return $value1 > $value2;
        }

        // String to DateTime conversion if one is DateTime and other is string
        if ($value1 instanceof \DateTime && is_string($value2)) {
            try {
                $value2 = new \DateTime($value2);
                return $value1 > $value2;
            } catch (\Exception) {
                return false;
            }
        }

        if (is_string($value1) && $value2 instanceof \DateTime) {
            try {
                $value1 = new \DateTime($value1);
                return $value1 > $value2;
            } catch (\Exception) {
                return false;
            }
        }

        // Numeric and string comparison
        return $value1 > $value2;
    }
}
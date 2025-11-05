<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsDue operator for checking if a date is due (past or current).
 * 
 * Returns true if value1 (date) is less than or equal to the current date/time.
 * Supports DateTime objects, Unix timestamps, and date strings.
 */
class IsDue implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $targetDate = $this->convertToDateTime($value1);
            $now = new \DateTime();
            
            return $targetDate <= $now;
        } catch (\Exception) {
            return false;
        }
    }

    private function convertToDateTime(mixed $value): \DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_numeric($value)) {
            // Unix timestamp
            return new \DateTime('@' . $value);
        }
        
        if (is_string($value)) {
            return new \DateTime($value);
        }
        
        throw new \InvalidArgumentException('Cannot convert value to DateTime');
    }
}
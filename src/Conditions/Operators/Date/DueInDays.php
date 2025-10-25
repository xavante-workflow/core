<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * DueInDays operator for checking if a date is due within N days.
 * 
 * Returns true if value1 (date) falls within the next N days from now.
 * value2 should contain the number of days (integer).
 */
class DueInDays implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $targetDate = $this->convertToDateTime($value1);
            $days = (int) $value2;
            
            $now = new \DateTime();
            $futureDate = (clone $now)->add(new \DateInterval('P' . $days . 'D'));
            
            // Check if target date is between now and future date
            return $targetDate >= $now && $targetDate <= $futureDate;
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
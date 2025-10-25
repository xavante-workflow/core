<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsToday operator for checking if a date falls on the current day.
 * 
 * Returns true if value1 (date) is on the same calendar day as today.
 * Time components are ignored - only the date portion is compared.
 */
class IsToday implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $targetDate = $this->convertToDateTime($value1);
            $today = new \DateTime();
            
            // Compare only the date portion (Y-m-d)
            return $targetDate->format('Y-m-d') === $today->format('Y-m-d');
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
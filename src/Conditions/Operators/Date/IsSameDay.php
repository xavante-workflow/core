<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * IsSameDay operator for checking if two dates fall on the same calendar day.
 * 
 * Returns true if value1 and value2 are on the same calendar day.
 * Time components are ignored - only the date portion is compared.
 */
class IsSameDay implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $date1 = $this->convertToDateTime($value1);
            $date2 = $this->convertToDateTime($value2);
            
            // Compare only the date portion (Y-m-d)
            return $date1->format('Y-m-d') === $date2->format('Y-m-d');
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
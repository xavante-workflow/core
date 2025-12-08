<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;
use Xavante\Helpers\ConvertToDateTime;

/**
 * DueInHours operator for checking if a date is due within N hours.
 * 
 * Returns true if value1 (date) falls within the next N hours from now.
 * value2 should contain the number of hours (integer).
 */
class DueInHours implements OperatorInterface
{
    use ConvertToDateTime;

    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $targetDate = $this->convertToDateTime($value1);
            $hours = (int) $value2;
            
            $now = new \DateTime();
            $futureDate = (clone $now)->add(new \DateInterval('PT' . $hours . 'H'));
            
            // Check if target date is between now and future date
            return $targetDate >= $now && $targetDate <= $futureDate;
        } catch (\Exception) {
            return false;
        }
    }

}
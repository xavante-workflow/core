<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;
use Xavante\Helpers\ConvertToDateTime;
/**
 * IsToday operator for checking if a date falls on the current day.
 * 
 * Returns true if value1 (date) is on the same calendar day as today.
 * Time components are ignored - only the date portion is compared.
 */
class IsToday implements OperatorInterface
{
    use ConvertToDateTime;

    // TODO: Review this implementation - the $value2 parameter is unused.
    // TODO: Consider also timezones in the comparison (future).
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

}
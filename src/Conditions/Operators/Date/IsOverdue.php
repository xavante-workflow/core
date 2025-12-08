<?php

namespace Xavante\Conditions\Operators\Date;

use Xavante\Conditions\Operators\OperatorInterface;
use Xavante\Helpers\ConvertToDateTime;
/**
 * IsOverdue operator for checking if a date is overdue (past the current date/time).
 * 
 * Returns true if value1 (date) is strictly less than the current date/time.
 * Supports DateTime objects, Unix timestamps, and date strings.
 */
class IsOverdue implements OperatorInterface
{
    use ConvertToDateTime;
    
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        try {
            $targetDate = $this->convertToDateTime($value1);
            $now = new \DateTime();
            
            return $targetDate < $now;
        } catch (\Exception) {
            return false;
        }
    }

}
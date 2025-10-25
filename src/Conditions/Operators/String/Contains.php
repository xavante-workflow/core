<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * Contains operator for checking if a string contains a substring.
 * 
 * Case-sensitive substring search.
 * Returns true if value1 contains value2 as a substring.
 */
class Contains implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        // Convert both values to strings
        $haystack = (string) $value1;
        $needle = (string) $value2;
        
        // Empty needle is always found
        if ($needle === '') {
            return true;
        }
        
        return str_contains($haystack, $needle);
    }
}
<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * EndsWith operator for checking if a string ends with a suffix.
 * 
 * Case-sensitive suffix matching.
 * Returns true if value1 ends with value2.
 */
class EndsWith implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        $haystack = (string) $value1;
        $needle = (string) $value2;
        
        // Empty needle always matches
        if ($needle === '') {
            return true;
        }
        
        return str_ends_with($haystack, $needle);
    }
}
<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * StartsWith operator for checking if a string starts with a prefix.
 * 
 * Case-sensitive prefix matching.
 * Returns true if value1 starts with value2.
 */
class StartsWith implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        $haystack = (string) $value1;
        $needle = (string) $value2;
        
        // Empty needle always matches
        if ($needle === '') {
            return true;
        }
        
        return str_starts_with($haystack, $needle);
    }
}
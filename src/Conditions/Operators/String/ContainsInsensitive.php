<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * ContainsInsensitive operator for case-insensitive substring search.
 * 
 * Case-insensitive substring search using strtolower.
 * Returns true if value1 contains value2 as a substring (case-insensitive).
 */
class ContainsInsensitive implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        // Convert both values to lowercase strings
        $haystack = strtolower((string) $value1);
        $needle = strtolower((string) $value2);
        
        // Empty needle is always found
        if ($needle === '') {
            return true;
        }
        
        return str_contains($haystack, $needle);
    }
}
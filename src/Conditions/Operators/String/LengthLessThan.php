<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * LengthLessThan operator for checking if string length is less than a value.
 * 
 * Returns true if the length of value1 is less than the numeric value in value2.
 */
class LengthLessThan implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        $text = (string) $value1;
        $threshold = (int) $value2;
        
        return strlen($text) < $threshold;
    }
}
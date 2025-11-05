<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * LengthEquals operator for checking string length.
 * 
 * Returns true if the length of value1 equals the numeric value in value2.
 */
class LengthEquals implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        $text = (string) $value1;
        $expectedLength = (int) $value2;
        
        return strlen($text) === $expectedLength;
    }
}
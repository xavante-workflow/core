<?php


namespace Xavante\Conditions\Operators;

class Equals
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return $value1 === $value2;
    }
}
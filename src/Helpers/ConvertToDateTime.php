<?php

namespace Xavante\Helpers;

trait ConvertToDateTime
{
    /**
     * Converts various date representations to a DateTime object.
     *
     * @param mixed $value The date value to convert (DateTime, timestamp, or string).
     * @return \DateTime The converted DateTime object.
     * @throws \InvalidArgumentException If the value cannot be converted.
     */
    protected function convertToDateTime(mixed $value): \DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_numeric($value)) {
            // Unix timestamp
            return new \DateTime('@' . $value);
        }
        
        if (is_string($value)) {
            return new \DateTime($value);
        }
        
        throw new \InvalidArgumentException('Cannot convert value to DateTime');
    }
}
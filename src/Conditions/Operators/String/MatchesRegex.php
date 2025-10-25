<?php

namespace Xavante\Conditions\Operators\String;

use Xavante\Conditions\Operators\OperatorInterface;

/**
 * MatchesRegex operator for regular expression pattern matching.
 * 
 * Uses preg_match for pattern validation with proper error handling.
 * Returns true if value1 matches the regex pattern in value2.
 * Returns false for invalid regex patterns without generating warnings.
 * 
 * @example 
 * // Valid patterns
 * evaluate('test123', '/\d+/') // true
 * evaluate('hello@example.com', '/^[^@]+@[^@]+$/') // true
 * 
 * // Invalid patterns
 * evaluate('test', 'invalid-regex') // false (no delimiters)
 * evaluate('test', '/[/') // false (unclosed bracket)
 */
class MatchesRegex implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        $text = (string) $value1;
        $pattern = (string) $value2;
        
        // Suppress warnings and check for errors
        $result = @preg_match($pattern, $text);
        
        // Check for preg_match errors (invalid regex)
        if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
            return false;
        }
        
        return $result === 1;
    }
}
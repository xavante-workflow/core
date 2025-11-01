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
        
        // Pre-validate the regex pattern to avoid any preg_match warnings
        if (!$this->isValidRegexPattern($pattern)) {
            return false;
        }
        
        // Execute the regex match
        $result = preg_match($pattern, $text);
        
        // Check for execution errors
        if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
            return false;
        }
        
        return $result === 1;
    }
    
    /**
     * Validates if a string is a valid regex pattern without executing it.
     * This prevents preg_match warnings from being generated.
     */
    private function isValidRegexPattern(string $pattern): bool
    {
        // Empty patterns are invalid
        if (empty($pattern)) {
            return false;
        }
        
        // Must have at least 3 characters (delimiter + content + delimiter)
        if (strlen($pattern) < 3) {
            return false;
        }
        
        $firstChar = $pattern[0];
        $lastChar = $pattern[strlen($pattern) - 1];
        
        // Check for valid delimiters (non-alphanumeric, non-backslash, non-NUL)
        if (ctype_alnum($firstChar) || $firstChar === '\\' || $firstChar === "\0") {
            return false;
        }
        
        // For most delimiters, first and last character should match
        // Exception: brackets, parentheses, braces, angle brackets
        $delimiterPairs = [
            '(' => ')',
            '[' => ']',
            '{' => '}',
            '<' => '>'
        ];
        
        if (isset($delimiterPairs[$firstChar])) {
            if ($lastChar !== $delimiterPairs[$firstChar]) {
                return false;
            }
        } else {
            if ($firstChar !== $lastChar) {
                return false;
            }
        }
        
        // Additional validation: check for unclosed character classes
        $content = substr($pattern, 1, -1);
        if (strpos($content, '[') !== false) {
            // Simple check for unclosed brackets
            $openBrackets = substr_count($content, '[');
            $closeBrackets = substr_count($content, ']');
            if ($openBrackets > $closeBrackets) {
                return false;
            }
        }
        
        return true;
    }
}
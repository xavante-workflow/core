<?php

namespace Xavante\Conditions\Operators;

/**
 * OperatorConstants provides type-safe constants for all operator names.
 * 
 * Use these constants instead of string literals to prevent typos and
 * enable IDE autocompletion when working with operators.
 * 
 * @example
 * // Instead of: OperatorRegistry::get('equals')
 * // Use: OperatorRegistry::get(OperatorConstants::EQUALS)
 */
class OperatorConstants
{
    // ========================================
    // COMPARISON OPERATORS
    // ========================================
    
    /** Strict equality check (===) */
    public const EQUALS = 'equals';
    public const EQ = 'eq';
    public const DOUBLE_EQUALS = '==';
    
    /** Strict inequality check (!==) */
    public const NOT_EQUALS = 'not_equals';
    public const NE = 'ne';
    public const NOT_EQUAL = '!=';
    
    /** Greater than comparison (>) */
    public const GREATER_THAN = 'greater_than';
    public const GT = 'gt';
    public const GREATER = '>';
    
    /** Less than comparison (<) */
    public const LESS_THAN = 'less_than';
    public const LT = 'lt';
    public const LESS = '<';
    
    /** Greater than or equal comparison (>=) */
    public const GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    public const GTE = 'gte';
    public const GREATER_OR_EQUAL = '>=';
    
    /** Less than or equal comparison (<=) */
    public const LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    public const LTE = 'lte';
    public const LESS_OR_EQUAL = '<=';

    // ========================================
    // LOGICAL OPERATORS
    // ========================================
    
    /** Check if value is empty using PHP's empty() */
    public const IS_EMPTY = 'is_empty';
    public const EMPTY = 'empty';
    
    /** Check if value is not empty */
    public const IS_NOT_EMPTY = 'is_not_empty';
    public const NOT_EMPTY = 'not_empty';
    
    /** Strict boolean true check (=== true) */
    public const IS_TRUE = 'is_true';
    public const TRUE = 'true';
    
    /** Strict boolean false check (=== false) */
    public const IS_FALSE = 'is_false';
    public const FALSE = 'false';
    
    /** Strict null check (=== null) */
    public const IS_NULL = 'is_null';
    public const NULL = 'null';
    
    /** Not null check (!== null) */
    public const IS_NOT_NULL = 'is_not_null';
    public const NOT_NULL = 'not_null';

    // ========================================
    // STRING OPERATORS
    // ========================================
    
    /** Case-sensitive substring search */
    public const CONTAINS = 'contains';
    
    /** Case-insensitive substring search */
    public const CONTAINS_INSENSITIVE = 'contains_insensitive';
    public const ICONTAINS = 'icontains';
    
    /** Check if string starts with prefix */
    public const STARTS_WITH = 'starts_with';
    
    /** Check if string ends with suffix */
    public const ENDS_WITH = 'ends_with';
    
    /** Regular expression pattern matching */
    public const MATCHES_REGEX = 'matches_regex';
    public const REGEX = 'regex';
    
    /** String length equals specific value */
    public const LENGTH_EQUALS = 'length_equals';
    public const LENGTH_EQ = 'length_eq';
    
    /** String length greater than value */
    public const LENGTH_GREATER_THAN = 'length_greater_than';
    public const LENGTH_GT = 'length_gt';
    
    /** String length less than value */
    public const LENGTH_LESS_THAN = 'length_less_than';
    public const LENGTH_LT = 'length_lt';

    // ========================================
    // DATE OPERATORS
    // ========================================
    
    /** Check if date is due (past or current) */
    public const IS_DUE = 'is_due';
    public const DUE = 'due';
    
    /** Check if date is overdue (past) */
    public const IS_OVERDUE = 'is_overdue';
    public const OVERDUE = 'overdue';
    
    /** Check if date is due within N days */
    public const DUE_IN_DAYS = 'due_in_days';
    
    /** Check if date is due within N hours */
    public const DUE_IN_HOURS = 'due_in_hours';
    
    /** Check if date is today */
    public const IS_TODAY = 'is_today';
    public const TODAY = 'today';
    
    /** Check if two dates are on same day */
    public const IS_SAME_DAY = 'is_same_day';
    public const SAME_DAY = 'same_day';

    // ========================================
    // UTILITY METHODS
    // ========================================
    
    /**
     * Get all comparison operator constants
     * 
     * @return array<string> Array of comparison operator names
     */
    public static function getComparisonOperators(): array
    {
        return [
            self::EQUALS, self::EQ, self::DOUBLE_EQUALS,
            self::NOT_EQUALS, self::NE, self::NOT_EQUAL,
            self::GREATER_THAN, self::GT, self::GREATER,
            self::LESS_THAN, self::LT, self::LESS,
            self::GREATER_THAN_OR_EQUAL, self::GTE, self::GREATER_OR_EQUAL,
            self::LESS_THAN_OR_EQUAL, self::LTE, self::LESS_OR_EQUAL,
        ];
    }
    
    /**
     * Get all logical operator constants
     * 
     * @return array<string> Array of logical operator names
     */
    public static function getLogicalOperators(): array
    {
        return [
            self::IS_EMPTY, self::EMPTY,
            self::IS_NOT_EMPTY, self::NOT_EMPTY,
            self::IS_TRUE, self::TRUE,
            self::IS_FALSE, self::FALSE,
            self::IS_NULL, self::NULL,
            self::IS_NOT_NULL, self::NOT_NULL,
        ];
    }
    
    /**
     * Get all string operator constants
     * 
     * @return array<string> Array of string operator names
     */
    public static function getStringOperators(): array
    {
        return [
            self::CONTAINS,
            self::CONTAINS_INSENSITIVE, self::ICONTAINS,
            self::STARTS_WITH,
            self::ENDS_WITH,
            self::MATCHES_REGEX, self::REGEX,
            self::LENGTH_EQUALS, self::LENGTH_EQ,
            self::LENGTH_GREATER_THAN, self::LENGTH_GT,
            self::LENGTH_LESS_THAN, self::LENGTH_LT,
        ];
    }
    
    /**
     * Get all date operator constants
     * 
     * @return array<string> Array of date operator names
     */
    public static function getDateOperators(): array
    {
        return [
            self::IS_DUE, self::DUE,
            self::IS_OVERDUE, self::OVERDUE,
            self::DUE_IN_DAYS,
            self::DUE_IN_HOURS,
            self::IS_TODAY, self::TODAY,
            self::IS_SAME_DAY, self::SAME_DAY,
        ];
    }
    
    /**
     * Get all operator constants
     * 
     * @return array<string> Array of all operator names
     */
    public static function getAllOperators(): array
    {
        return array_merge(
            self::getComparisonOperators(),
            self::getLogicalOperators(),
            self::getStringOperators(),
            self::getDateOperators()
        );
    }
    
    /**
     * Check if an operator name is valid
     * 
     * @param string $operator The operator name to check
     * @return bool True if the operator is valid
     */
    public static function isValidOperator(string $operator): bool
    {
        return in_array($operator, self::getAllOperators(), true);
    }
}
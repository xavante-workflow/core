<?php

namespace Xavante\Conditions\Operators;

use Xavante\Conditions\Operators\Comparison\Equals;
use Xavante\Conditions\Operators\Comparison\NotEquals;
use Xavante\Conditions\Operators\Comparison\GreaterThan;
use Xavante\Conditions\Operators\Comparison\LessThan;
use Xavante\Conditions\Operators\Comparison\GreaterThanOrEqual;
use Xavante\Conditions\Operators\Comparison\LessThanOrEqual;
use Xavante\Conditions\Operators\Logical\IsEmpty;
use Xavante\Conditions\Operators\Logical\IsNotEmpty;
use Xavante\Conditions\Operators\Logical\IsTrue;
use Xavante\Conditions\Operators\Logical\IsFalse;
use Xavante\Conditions\Operators\Logical\IsNull;
use Xavante\Conditions\Operators\Logical\IsNotNull;
use Xavante\Conditions\Operators\String\Contains;
use Xavante\Conditions\Operators\String\ContainsInsensitive;
use Xavante\Conditions\Operators\String\StartsWith;
use Xavante\Conditions\Operators\String\EndsWith;
use Xavante\Conditions\Operators\String\MatchesRegex;
use Xavante\Conditions\Operators\String\LengthEquals;
use Xavante\Conditions\Operators\String\LengthGreaterThan;
use Xavante\Conditions\Operators\String\LengthLessThan;
use Xavante\Conditions\Operators\Date\IsDue;
use Xavante\Conditions\Operators\Date\IsOverdue;
use Xavante\Conditions\Operators\Date\DueInDays;
use Xavante\Conditions\Operators\Date\DueInHours;
use Xavante\Conditions\Operators\Date\IsToday;
use Xavante\Conditions\Operators\Date\IsSameDay;

/**
 * OperatorRegistry provides a centralized way to access all available operators.
 * 
 * This registry maps operator names to their corresponding class instances,
 * making it easy to use operators in workflow conditions by name.
 */
class OperatorRegistry
{
    private static array $operators = [];

    /**
     * Get an operator instance by name.
     * 
     * @param string $name The operator name
     * @return OperatorInterface The operator instance
     * @throws \InvalidArgumentException If the operator is not found
     */
    public static function get(string $name): OperatorInterface
    {
        if (empty(self::$operators)) {
            self::initialize();
        }

        if (!isset(self::$operators[$name])) {
            throw new \InvalidArgumentException("Operator '$name' not found");
        }

        return self::$operators[$name];
    }

    /**
     * Get all available operator names.
     * 
     * @return array<string> List of operator names
     */
    public static function getAvailableOperators(): array
    {
        if (empty(self::$operators)) {
            self::initialize();
        }

        return array_keys(self::$operators);
    }

    /**
     * Check if an operator exists.
     * 
     * @param string $name The operator name
     * @return bool True if the operator exists
     */
    public static function has(string $name): bool
    {
        if (empty(self::$operators)) {
            self::initialize();
        }

        return isset(self::$operators[$name]);
    }

    /**
     * Register a custom operator.
     * 
     * @param string $name The operator name
     * @param OperatorInterface $operator The operator instance
     */
    public static function register(string $name, OperatorInterface $operator): void
    {
        if (empty(self::$operators)) {
            self::initialize();
        }

        self::$operators[$name] = $operator;
    }

    /**
     * Initialize the operators registry with all built-in operators.
     */
    private static function initialize(): void
    {
        self::$operators = [
            // Comparison operators
            OperatorConstants::EQUALS => new Equals(),
            OperatorConstants::EQ => new Equals(),
            OperatorConstants::DOUBLE_EQUALS => new Equals(),
            OperatorConstants::NOT_EQUALS => new NotEquals(),
            OperatorConstants::NE => new NotEquals(),
            OperatorConstants::NOT_EQUAL => new NotEquals(),
            OperatorConstants::GREATER_THAN => new GreaterThan(),
            OperatorConstants::GT => new GreaterThan(),
            OperatorConstants::GREATER => new GreaterThan(),
            OperatorConstants::LESS_THAN => new LessThan(),
            OperatorConstants::LT => new LessThan(),
            OperatorConstants::LESS => new LessThan(),
            OperatorConstants::GREATER_THAN_OR_EQUAL => new GreaterThanOrEqual(),
            OperatorConstants::GTE => new GreaterThanOrEqual(),
            OperatorConstants::GREATER_OR_EQUAL => new GreaterThanOrEqual(),
            OperatorConstants::LESS_THAN_OR_EQUAL => new LessThanOrEqual(),
            OperatorConstants::LTE => new LessThanOrEqual(),
            OperatorConstants::LESS_OR_EQUAL => new LessThanOrEqual(),

            // Logical operators
            OperatorConstants::IS_EMPTY => new IsEmpty(),
            OperatorConstants::EMPTY => new IsEmpty(),
            OperatorConstants::IS_NOT_EMPTY => new IsNotEmpty(),
            OperatorConstants::NOT_EMPTY => new IsNotEmpty(),
            OperatorConstants::IS_TRUE => new IsTrue(),
            OperatorConstants::TRUE => new IsTrue(),
            OperatorConstants::IS_FALSE => new IsFalse(),
            OperatorConstants::FALSE => new IsFalse(),
            OperatorConstants::IS_NULL => new IsNull(),
            OperatorConstants::NULL => new IsNull(),
            OperatorConstants::IS_NOT_NULL => new IsNotNull(),
            OperatorConstants::NOT_NULL => new IsNotNull(),

            // String operators
            OperatorConstants::CONTAINS => new Contains(),
            OperatorConstants::CONTAINS_INSENSITIVE => new ContainsInsensitive(),
            OperatorConstants::ICONTAINS => new ContainsInsensitive(),
            OperatorConstants::STARTS_WITH => new StartsWith(),
            OperatorConstants::ENDS_WITH => new EndsWith(),
            OperatorConstants::MATCHES_REGEX => new MatchesRegex(),
            OperatorConstants::REGEX => new MatchesRegex(),
            OperatorConstants::LENGTH_EQUALS => new LengthEquals(),
            OperatorConstants::LENGTH_EQ => new LengthEquals(),
            OperatorConstants::LENGTH_GREATER_THAN => new LengthGreaterThan(),
            OperatorConstants::LENGTH_GT => new LengthGreaterThan(),
            OperatorConstants::LENGTH_LESS_THAN => new LengthLessThan(),
            OperatorConstants::LENGTH_LT => new LengthLessThan(),

            // Date operators
            OperatorConstants::IS_DUE => new IsDue(),
            OperatorConstants::DUE => new IsDue(),
            OperatorConstants::IS_OVERDUE => new IsOverdue(),
            OperatorConstants::OVERDUE => new IsOverdue(),
            OperatorConstants::DUE_IN_DAYS => new DueInDays(),
            OperatorConstants::DUE_IN_HOURS => new DueInHours(),
            OperatorConstants::IS_TODAY => new IsToday(),
            OperatorConstants::TODAY => new IsToday(),
            OperatorConstants::IS_SAME_DAY => new IsSameDay(),
            OperatorConstants::SAME_DAY => new IsSameDay(),
        ];
    }
}
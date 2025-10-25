<?php

namespace Xavante\Conditions\Operators;

/**
 * Interface OperatorInterface
 * 
 * Base interface for all condition operators in the Xavante workflow engine.
 * All operators must implement this interface to ensure consistent behavior
 * across comparison, logical, string, and date operations.
 */
interface OperatorInterface
{
    /**
     * Evaluate the condition using the operator logic.
     * 
     * @param mixed $value1 The first operand (usually the variable value)
     * @param mixed $value2 The second operand (usually the expected value)
     * @return bool True if the condition is met, false otherwise
     */
    public function evaluate(mixed $value1, mixed $value2): bool;
}
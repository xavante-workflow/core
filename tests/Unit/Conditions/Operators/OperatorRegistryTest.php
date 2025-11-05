<?php

namespace Tests\Unit\Conditions\Operators;

use PHPUnit\Framework\TestCase;
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

/**
 * OperatorRegistryTest demonstrates usage of all operators
 * and serves as documentation for their behavior.
 */
class OperatorRegistryTest extends TestCase
{
    public function testComparisonOperators(): void
    {
        // Equals
        $equals = OperatorRegistry::get(OperatorConstants::EQUALS);
        $this->assertTrue($equals->evaluate('test', 'test'));
        $this->assertTrue($equals->evaluate(42, 42));
        $this->assertFalse($equals->evaluate('42', 42)); // Strict comparison
        $this->assertFalse($equals->evaluate('Test', 'test')); // Case sensitive

        // Not Equals
        $notEquals = OperatorRegistry::get(OperatorConstants::NOT_EQUALS);
        $this->assertTrue($notEquals->evaluate('test', 'TEST'));
        $this->assertTrue($notEquals->evaluate(42, '42'));
        $this->assertFalse($notEquals->evaluate('same', 'same'));

        // Greater Than
        $gt = OperatorRegistry::get(OperatorConstants::GREATER_THAN);
        $this->assertTrue($gt->evaluate(10, 5));
        $this->assertTrue($gt->evaluate('b', 'a')); // Lexicographic
        $this->assertFalse($gt->evaluate(5, 10));
        $this->assertFalse($gt->evaluate(5, 5));

        // Less Than
        $lt = OperatorRegistry::get(OperatorConstants::LESS_THAN);
        $this->assertTrue($lt->evaluate(5, 10));
        $this->assertTrue($lt->evaluate('a', 'b'));
        $this->assertFalse($lt->evaluate(10, 5));

        // Greater Than or Equal
        $gte = OperatorRegistry::get(OperatorConstants::GTE);
        $this->assertTrue($gte->evaluate(10, 5));
        $this->assertTrue($gte->evaluate(5, 5));
        $this->assertFalse($gte->evaluate(5, 10));

        // Less Than or Equal
        $lte = OperatorRegistry::get(OperatorConstants::LTE);
        $this->assertTrue($lte->evaluate(5, 10));
        $this->assertTrue($lte->evaluate(5, 5));
        $this->assertFalse($lte->evaluate(10, 5));
    }

    public function testLogicalOperators(): void
    {
        // IsEmpty
        $isEmpty = OperatorRegistry::get(OperatorConstants::IS_EMPTY);
        $this->assertTrue($isEmpty->evaluate('', null));
        $this->assertTrue($isEmpty->evaluate(null, null));
        $this->assertTrue($isEmpty->evaluate(0, null));
        $this->assertTrue($isEmpty->evaluate([], null));
        $this->assertFalse($isEmpty->evaluate('text', null));
        $this->assertFalse($isEmpty->evaluate(1, null));

        // IsNotEmpty
        $isNotEmpty = OperatorRegistry::get(OperatorConstants::IS_NOT_EMPTY);
        $this->assertTrue($isNotEmpty->evaluate('text', null));
        $this->assertTrue($isNotEmpty->evaluate(1, null));
        $this->assertTrue($isNotEmpty->evaluate([1], null));
        $this->assertFalse($isNotEmpty->evaluate('', null));
        $this->assertFalse($isNotEmpty->evaluate(null, null));

        // IsTrue
        $isTrue = OperatorRegistry::get(OperatorConstants::IS_TRUE);
        $this->assertTrue($isTrue->evaluate(true, null));
        $this->assertFalse($isTrue->evaluate(1, null)); // Strict boolean check
        $this->assertFalse($isTrue->evaluate('true', null));
        $this->assertFalse($isTrue->evaluate(false, null));

        // IsFalse
        $isFalse = OperatorRegistry::get(OperatorConstants::IS_FALSE);
        $this->assertTrue($isFalse->evaluate(false, null));
        $this->assertFalse($isFalse->evaluate(0, null)); // Strict boolean check
        $this->assertFalse($isFalse->evaluate('', null));
        $this->assertFalse($isFalse->evaluate(true, null));

        // IsNull
        $isNull = OperatorRegistry::get(OperatorConstants::IS_NULL);
        $this->assertTrue($isNull->evaluate(null, null));
        $this->assertFalse($isNull->evaluate(0, null));
        $this->assertFalse($isNull->evaluate('', null));

        // IsNotNull
        $isNotNull = OperatorRegistry::get(OperatorConstants::IS_NOT_NULL);
        $this->assertTrue($isNotNull->evaluate(0, null));
        $this->assertTrue($isNotNull->evaluate('', null));
        $this->assertTrue($isNotNull->evaluate(false, null));
        $this->assertFalse($isNotNull->evaluate(null, null));
    }

    public function testStringOperators(): void
    {
        // Contains
        $contains = OperatorRegistry::get(OperatorConstants::CONTAINS);
        $this->assertTrue($contains->evaluate('hello world', 'world'));
        $this->assertTrue($contains->evaluate('test', 'test'));
        $this->assertTrue($contains->evaluate('anything', '')); // Empty string always found
        $this->assertFalse($contains->evaluate('hello world', 'WORLD')); // Case sensitive

        // Contains Insensitive
        $icontains = OperatorRegistry::get(OperatorConstants::ICONTAINS);
        $this->assertTrue($icontains->evaluate('Hello World', 'WORLD'));
        $this->assertTrue($icontains->evaluate('TEST', 'test'));
        $this->assertFalse($icontains->evaluate('hello', 'xyz'));

        // Starts With
        $startsWith = OperatorRegistry::get(OperatorConstants::STARTS_WITH);
        $this->assertTrue($startsWith->evaluate('hello world', 'hello'));
        $this->assertTrue($startsWith->evaluate('test', ''));
        $this->assertFalse($startsWith->evaluate('hello world', 'world'));

        // Ends With
        $endsWith = OperatorRegistry::get(OperatorConstants::ENDS_WITH);
        $this->assertTrue($endsWith->evaluate('hello world', 'world'));
        $this->assertTrue($endsWith->evaluate('test', ''));
        $this->assertFalse($endsWith->evaluate('hello world', 'hello'));

        // Matches Regex
        $regex = OperatorRegistry::get(OperatorConstants::REGEX);
        $this->assertTrue($regex->evaluate('test123', '/\d+/'));
        $this->assertTrue($regex->evaluate('hello@example.com', '/^[^@]+@[^@]+$/'));
        $this->assertFalse($regex->evaluate('abc', '/\d+/'));
        
        // Test various invalid regex patterns (should not produce warnings)
        $this->assertFalse($regex->evaluate('test', 'invalid-regex')); // No delimiters
        $this->assertFalse($regex->evaluate('test', '/[/')); // Unclosed bracket
        $this->assertFalse($regex->evaluate('test', 'abc')); // No delimiters
        $this->assertFalse($regex->evaluate('test', '')); // Empty pattern

        // Length Equals
        $lengthEq = OperatorRegistry::get(OperatorConstants::LENGTH_EQUALS);
        $this->assertTrue($lengthEq->evaluate('hello', 5));
        $this->assertTrue($lengthEq->evaluate('', 0));
        $this->assertFalse($lengthEq->evaluate('test', 10));

        // Length Greater Than
        $lengthGt = OperatorRegistry::get(OperatorConstants::LENGTH_GT);
        $this->assertTrue($lengthGt->evaluate('hello', 3));
        $this->assertFalse($lengthGt->evaluate('hi', 5));
        $this->assertFalse($lengthGt->evaluate('test', 4)); // Equal length

        // Length Less Than
        $lengthLt = OperatorRegistry::get(OperatorConstants::LENGTH_LT);
        $this->assertTrue($lengthLt->evaluate('hi', 5));
        $this->assertFalse($lengthLt->evaluate('hello', 3));
        $this->assertFalse($lengthLt->evaluate('test', 4)); // Equal length
    }

    public function testDateOperators(): void
    {
        $now = new \DateTime();
        $past = (clone $now)->sub(new \DateInterval('P1D')); // 1 day ago
        $future = (clone $now)->add(new \DateInterval('P1D')); // 1 day from now
        $futureHours = (clone $now)->add(new \DateInterval('PT2H')); // 2 hours from now

        // IsDue
        $isDue = OperatorRegistry::get(OperatorConstants::IS_DUE);
        $this->assertTrue($isDue->evaluate($past, null));
        // Note: $now might be slightly different due to test execution time, so we use a more lenient approach
        $testNow = new \DateTime(); // Fresh current time
        $this->assertTrue($isDue->evaluate($testNow, null));
        $this->assertFalse($isDue->evaluate($future, null));

        // IsOverdue
        $isOverdue = OperatorRegistry::get(OperatorConstants::IS_OVERDUE);
        $this->assertTrue($isOverdue->evaluate($past, null));
        // Use a slightly past date to ensure it's truly overdue
        $slightlyPast = (clone $now)->sub(new \DateInterval('PT1M')); // 1 minute ago
        $this->assertTrue($isOverdue->evaluate($slightlyPast, null));
        $this->assertFalse($isOverdue->evaluate($future, null));

        // Due In Days
        $dueInDays = OperatorRegistry::get(OperatorConstants::DUE_IN_DAYS);
        $this->assertTrue($dueInDays->evaluate($future, 2)); // Within 2 days
        $this->assertFalse($dueInDays->evaluate($past, 2)); // Past dates not "due in"
        
        $farFuture = (clone $now)->add(new \DateInterval('P10D'));
        $this->assertFalse($dueInDays->evaluate($farFuture, 2)); // Too far in future

        // Due In Hours
        $dueInHours = OperatorRegistry::get(OperatorConstants::DUE_IN_HOURS);
        $this->assertTrue($dueInHours->evaluate($futureHours, 5)); // Within 5 hours
        $this->assertFalse($dueInHours->evaluate($past, 5));

        // IsToday
        $isToday = OperatorRegistry::get(OperatorConstants::IS_TODAY);
        $this->assertTrue($isToday->evaluate($now, null));
        $this->assertFalse($isToday->evaluate($past, null));
        
        // Same time but different day
        $yesterday = (clone $now)->sub(new \DateInterval('P1D'));
        $this->assertFalse($isToday->evaluate($yesterday, null));

        // IsSameDay
        $isSameDay = OperatorRegistry::get(OperatorConstants::IS_SAME_DAY);
        $nowCopy = clone $now;
        $this->assertTrue($isSameDay->evaluate($now, $nowCopy));
        $this->assertFalse($isSameDay->evaluate($now, $past));
        
        // Same day but different time (use 2 hours to avoid crossing midnight)
        $sameDay = (clone $now)->add(new \DateInterval('PT2H'));
        $this->assertTrue($isSameDay->evaluate($now, $sameDay));
    }

    public function testOperatorAliases(): void
    {
        // Test that aliases work correctly
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::EQ));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::DOUBLE_EQUALS));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::NOT_EQUAL));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::GREATER));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::LESS));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::GREATER_OR_EQUAL));
        $this->assertTrue(OperatorRegistry::has(OperatorConstants::LESS_OR_EQUAL));

        // Test that aliases return the same results
        $equals1 = OperatorRegistry::get(OperatorConstants::EQUALS);
        $equals2 = OperatorRegistry::get(OperatorConstants::EQ);
        $equals3 = OperatorRegistry::get(OperatorConstants::DOUBLE_EQUALS);
        
        $this->assertTrue($equals1->evaluate(5, 5));
        $this->assertTrue($equals2->evaluate(5, 5));
        $this->assertTrue($equals3->evaluate(5, 5));
    }

    public function testCustomOperatorRegistration(): void
    {
        // Create a custom operator
        $customOp = new class implements \Xavante\Conditions\Operators\OperatorInterface {
            public function evaluate(mixed $value1, mixed $value2): bool {
                return strlen((string)$value1) === strlen((string)$value2);
            }
        };

        // Register it
        OperatorRegistry::register('same_length', $customOp);

        // Test it works
        $this->assertTrue(OperatorRegistry::has('same_length'));
        $op = OperatorRegistry::get('same_length');
        $this->assertTrue($op->evaluate('hello', 'world')); // Both 5 chars
        $this->assertFalse($op->evaluate('hi', 'hello')); // Different lengths
    }

    public function testAvailableOperators(): void
    {
        $operators = OperatorRegistry::getAvailableOperators();
        
        $this->assertIsArray($operators);
        $this->assertContains(OperatorConstants::EQUALS, $operators);
        $this->assertContains(OperatorConstants::GREATER_THAN, $operators);
        $this->assertContains(OperatorConstants::CONTAINS, $operators);
        $this->assertContains(OperatorConstants::IS_DUE, $operators);
        
        // Should include aliases
        $this->assertContains(OperatorConstants::EQ, $operators);
        $this->assertContains(OperatorConstants::GREATER, $operators);
        $this->assertContains(OperatorConstants::ICONTAINS, $operators);
    }

    public function testInvalidOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'nonexistent' not found");
        
        OperatorRegistry::get('nonexistent');
    }

    public function testOperatorConstants(): void
    {
        // Test utility methods
        $comparisonOps = OperatorConstants::getComparisonOperators();
        $this->assertContains(OperatorConstants::EQUALS, $comparisonOps);
        $this->assertContains(OperatorConstants::GREATER_THAN, $comparisonOps);

        $logicalOps = OperatorConstants::getLogicalOperators();
        $this->assertContains(OperatorConstants::IS_EMPTY, $logicalOps);
        $this->assertContains(OperatorConstants::IS_TRUE, $logicalOps);

        $stringOps = OperatorConstants::getStringOperators();
        $this->assertContains(OperatorConstants::CONTAINS, $stringOps);
        $this->assertContains(OperatorConstants::REGEX, $stringOps);

        $dateOps = OperatorConstants::getDateOperators();
        $this->assertContains(OperatorConstants::IS_DUE, $dateOps);
        $this->assertContains(OperatorConstants::IS_TODAY, $dateOps);

        // Test validation
        $this->assertTrue(OperatorConstants::isValidOperator(OperatorConstants::EQUALS));
        $this->assertFalse(OperatorConstants::isValidOperator('invalid_operator'));

        // Test getAllOperators includes all categories
        $allOps = OperatorConstants::getAllOperators();
        $this->assertGreaterThan(50, count($allOps)); // Should have 53+ operators
        $this->assertContains(OperatorConstants::EQUALS, $allOps);
        $this->assertContains(OperatorConstants::IS_EMPTY, $allOps);
        $this->assertContains(OperatorConstants::CONTAINS, $allOps);
        $this->assertContains(OperatorConstants::IS_DUE, $allOps);
    }
}
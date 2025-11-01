# Xavante Workflow Operators Guide

This document provides a comprehensive guide to all available operators in the Xavante Workflow Engine for use in workflow conditions and guards.

## Overview

Operators are used in workflow transitions and guards to evaluate conditions. Each operator implements the `OperatorInterface` and can be used through the `OperatorRegistry` by name or alias.

**💡 Best Practice**: Use `OperatorConstants` instead of string literals to prevent typos and enable IDE autocompletion:

```php
// ✅ Recommended - Type-safe with IDE support
use Xavante\Conditions\Operators\OperatorConstants;
$condition = new Condition('status', OperatorConstants::EQUALS, 'approved');

// ❌ Discouraged - Prone to typos
$condition = new Condition('status', 'equals', 'approved');
```

## Quick Reference

### Comparison Operators

| Operator | Aliases | Description | Example |
|----------|---------|-------------|---------|
| `equals` | `eq`, `==` | Strict equality check | `document.status == 'approved'` |
| `not_equals` | `ne`, `!=` | Strict inequality check | `user.role != 'guest'` |
| `greater_than` | `gt`, `>` | Numeric/string comparison | `document.priority > 5` |
| `less_than` | `lt`, `<` | Numeric/string comparison | `user.age < 18` |
| `greater_than_or_equal` | `gte`, `>=` | Numeric/string comparison | `order.total >= 100` |
| `less_than_or_equal` | `lte`, `<=` | Numeric/string comparison | `retry.count <= 3` |

### Logical Operators

| Operator | Aliases | Description | Example |
|----------|---------|-------------|---------|
| `is_empty` | `empty` | Checks if value is empty | `comment.text == empty` |
| `is_not_empty` | `not_empty` | Checks if value is not empty | `user.email != empty` |
| `is_true` | `true` | Strict boolean true check | `feature.enabled == true` |
| `is_false` | `false` | Strict boolean false check | `payment.processed == false` |
| `is_null` | `null` | Checks if value is null | `error.message == null` |
| `is_not_null` | `not_null` | Checks if value is not null | `user.id != null` |

### String Operators

| Operator | Aliases | Description | Example |
|----------|---------|-------------|---------|
| `contains` | - | Case-sensitive substring search | `email.address contains '@company.com'` |
| `contains_insensitive` | `icontains` | Case-insensitive substring search | `document.title icontains 'urgent'` |
| `starts_with` | - | Checks if string starts with prefix | `document.id starts_with 'DOC-'` |
| `ends_with` | - | Checks if string ends with suffix | `file.name ends_with '.pdf'` |
| `matches_regex` | `regex` | Regular expression matching | `phone.number regex '/^\+?[0-9-() ]+$/'` |
| `length_equals` | `length_eq` | String length comparison | `password length_eq 8` |
| `length_greater_than` | `length_gt` | String length comparison | `comment length_gt 10` |
| `length_less_than` | `length_lt` | String length comparison | `username length_lt 20` |

### Date Operators

| Operator | Aliases | Description | Example |
|----------|---------|-------------|---------|
| `is_due` | `due` | Checks if date is due (past/current) | `task.deadline is_due` |
| `is_overdue` | `overdue` | Checks if date is overdue (past) | `invoice.due_date overdue` |
| `due_in_days` | - | Checks if due within N days | `reminder.date due_in_days 7` |
| `due_in_hours` | - | Checks if due within N hours | `meeting.time due_in_hours 2` |
| `is_today` | `today` | Checks if date is today | `event.date is_today` |
| `is_same_day` | `same_day` | Compares two dates (same day) | `start.date same_day end.date` |

## Usage Examples

### In Workflow Conditions

```php
// Create a condition for workflow transitions
use Xavante\Models\Domain\Condition;
use Xavante\Conditions\Operators\OperatorConstants;

// Basic equality check (recommended)
$condition1 = new Condition('document.status', OperatorConstants::EQUALS, 'approved');

// Using symbolic aliases for readability  
$condition2 = new Condition('user.age', OperatorConstants::GREATER_OR_EQUAL, 18);

// String operations
$condition3 = new Condition('email', OperatorConstants::CONTAINS, '@company.com');

// Date operations
$condition4 = new Condition('deadline', OperatorConstants::DUE_IN_DAYS, 3);

// Logical checks
$condition5 = new Condition('comment', OperatorConstants::IS_NOT_EMPTY, null);
```

### Programmatic Operator Usage

```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

// Get operator by constant (recommended)
$equalsOp = OperatorRegistry::get(OperatorConstants::EQUALS);
$result = $equalsOp->evaluate('test', 'test'); // true

// Use symbolic aliases with constants
$gtOp = OperatorRegistry::get(OperatorConstants::GREATER);
$result = $gtOp->evaluate(10, 5); // true

// String operations
$containsOp = OperatorRegistry::get(OperatorConstants::CONTAINS);
$result = $containsOp->evaluate('hello world', 'world'); // true

// Date operations
$isDueOp = OperatorRegistry::get(OperatorConstants::IS_DUE);
$result = $isDueOp->evaluate(new DateTime('2024-01-01'), null); // true if past
```

### Custom Operator Registration

```php
use Xavante\Conditions\Operators\OperatorInterface;

// Create a custom operator
class IsEvenOperator implements OperatorInterface 
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        return (int)$value1 % 2 === 0;
    }
}

// Register it
OperatorRegistry::register('is_even', new IsEvenOperator());

// Use it with string (works but not recommended)
$condition = new Condition('user.id', 'is_even', null);

// Better: Create constants for custom operators
class CustomOperatorConstants 
{
    public const IS_EVEN = 'is_even';
}

$condition = new Condition('user.id', CustomOperatorConstants::IS_EVEN, null);
```

## Implementation Details

### Type Handling

- **Comparison operators**: Handle numeric values, strings (lexicographic), and DateTime objects
- **String operators**: Auto-convert inputs to strings
- **Date operators**: Accept DateTime objects, Unix timestamps, and parseable date strings
- **Logical operators**: Use PHP's type system (strict for boolean checks, empty() for emptiness)

### Error Handling

- **Regex patterns**: Invalid patterns return `false` without warnings
- **Date parsing**: Invalid date strings return `false` gracefully  
- **Null values**: Handled gracefully in all comparisons
- **Type mismatches**: Use PHP's natural comparison rules
- **Edge cases**: Empty strings, zero values, and boundary conditions handled consistently

### Performance Considerations

- Operators are instantiated once and cached in the registry
- Date parsing is done on-demand and cached per evaluation
- Regex compilation happens during evaluation (consider pre-compilation for repeated use)

## Extending Operators

### Creating Custom Operators

1. Implement the `OperatorInterface`
2. Add proper error handling
3. Document expected input types
4. Register with the `OperatorRegistry`

```php
use Xavante\Conditions\Operators\OperatorInterface;

class CustomOperator implements OperatorInterface
{
    public function evaluate(mixed $value1, mixed $value2): bool
    {
        // Your custom logic here
        return true;
    }
}
```

### Organizing Operators

- **Comparison**: Numeric and basic value comparisons
- **Logical**: Boolean and existence checks  
- **String**: Text-specific operations
- **Date**: Time and calendar operations
- **Custom**: Domain-specific operators

Place new operators in appropriate subdirectories under `src/Conditions/Operators/`.

## Operator Constants

The `OperatorConstants` class provides type-safe constants for all operator names, preventing typos and enabling IDE autocompletion.

### Benefits of Using Constants

- **Type Safety**: Compile-time error detection for invalid operators
- **IDE Support**: Autocompletion and refactoring support
- **Maintainability**: Centralized operator name management
- **Documentation**: Self-documenting code with descriptive constant names

### Available Constant Categories

```php
use Xavante\Conditions\Operators\OperatorConstants;

// Get operators by category
$comparisonOps = OperatorConstants::getComparisonOperators();
$logicalOps = OperatorConstants::getLogicalOperators(); 
$stringOps = OperatorConstants::getStringOperators();
$dateOps = OperatorConstants::getDateOperators();

// Get all operators
$allOps = OperatorConstants::getAllOperators();

// Validate operator names
$isValid = OperatorConstants::isValidOperator('equals'); // true
$isValid = OperatorConstants::isValidOperator('invalid'); // false
```

### Constant Naming Convention

- **Primary names**: Descriptive and clear (`EQUALS`, `GREATER_THAN`, `IS_EMPTY`)
- **Short aliases**: Convenient shortcuts (`EQ`, `GT`, `EMPTY`) 
- **Symbolic aliases**: Mathematical symbols (`DOUBLE_EQUALS`, `GREATER`, `LESS`)

### Migration from String Literals

Replace string literals with constants gradually:

```php
// Before: String literals (error-prone)
new Condition('status', 'equals', 'active');
new Condition('count', '>', 0);
new Condition('text', 'contains', 'error');

// After: Type-safe constants (recommended)
new Condition('status', OperatorConstants::EQUALS, 'active');  
new Condition('count', OperatorConstants::GREATER, 0);
new Condition('text', OperatorConstants::CONTAINS, 'error');
```

## Testing

See `tests/Unit/Conditions/Operators/OperatorRegistryTest.php` for comprehensive examples of all operators in action.

```bash
# Run operator tests
docker compose exec app vendor/bin/phpunit tests/Unit/Conditions/Operators/
```
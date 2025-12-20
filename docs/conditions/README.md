# Xavante Condition Operators

Xavante provides a rich set of condition operators for workflow guards, transitions, and variable checks. Operators are organized by type and implement a common interface for flexible, type-safe evaluation.

## Usage Pattern

Operators are used via the `OperatorRegistry` or directly by class:

```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$operator = OperatorRegistry::get(OperatorConstants::EQUALS);
$result = $operator->evaluate($value1, $value2);
```

All operators implement:
```php
public function evaluate(mixed $value1, mixed $value2): bool
```

---

## Operator Types

- **Comparison**: Numeric, string, and date comparisons (equals, greater than, less than, etc.)
- **Logical**: Null/empty checks, boolean logic
- **String**: Substring, pattern, and length checks
- **Date**: Due, overdue, same day, etc.

---

## Operator Reference

### Comparison Operators

- **Equals**: Checks if two values are equal (supports numbers, strings, dates)
- **NotEquals**: Checks if two values are not equal
- **GreaterThan**: Checks if value1 > value2 (numbers, strings, dates)
- **GreaterThanOrEqual**: Checks if value1 >= value2
- **LessThan**: Checks if value1 < value2
- **LessThanOrEqual**: Checks if value1 <= value2

### Logical Operators

- **IsNull**: Checks if value is null
- **IsNotNull**: Checks if value is not null
- **IsEmpty**: Checks if value is empty (null, '', 0, [], etc.)
- **IsNotEmpty**: Checks if value is not empty
- **IsTrue**: Checks if value is true
- **IsFalse**: Checks if value is false

### String Operators

- **Contains**: Checks if value1 contains value2 (case-sensitive)
- **ContainsInsensitive**: Case-insensitive contains
- **StartsWith**: Checks if value1 starts with value2
- **EndsWith**: Checks if value1 ends with value2
- **LengthEquals**: Checks if string length equals value2
- **LengthGreaterThan**: Checks if string length > value2
- **LengthLessThan**: Checks if string length < value2
- **MatchesRegex**: Checks if value1 matches regex pattern value2

### Date Operators

- **IsDue**: Checks if a date/time is due (<= now)
- **IsOverdue**: Checks if a date/time is in the past
- **IsToday**: Checks if a date is today
- **IsSameDay**: Checks if two dates are the same day
- **DueInDays**: Checks if a date is due in N days
- **DueInHours**: Checks if a date is due in N hours

---

## Example: GreaterThan Operator

```php
use Xavante\Conditions\Operators\Comparison\GreaterThan;

$op = new GreaterThan();
$op->evaluate(5, 3); // true
$op->evaluate('b', 'a'); // true
$op->evaluate(new DateTime('2024-01-01'), new DateTime('2023-12-31')); // true
```

## Example: IsEmpty Operator

```php
use Xavante\Conditions\Operators\Logical\IsEmpty;

$op = new IsEmpty();
$op->evaluate('', null); // true
$op->evaluate('foo', null); // false
```

## Example: Contains Operator

```php
use Xavante\Conditions\Operators\String\Contains;

$op = new Contains();
$op->evaluate('hello world', 'world'); // true
$op->evaluate('abc', 'd'); // false
```

## Example: IsDue Operator

```php
use Xavante\Conditions\Operators\Date\IsDue;

$op = new IsDue();
$op->evaluate('2024-01-01 00:00:00', null); // true if date is past or now
```

---

For a full list and usage, see `docs/operators.md` and the `OperatorRegistry` class.

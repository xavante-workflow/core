# Equals Operator

Checks if two values are equal. Supports numbers, strings, and dates. This is the most common comparison operator.

## Usage
```php
use Xavante\Conditions\Operators\Comparison\Equals;

$op = new Equals();
$op->evaluate(5, 5); // true
$op->evaluate('foo', 'foo'); // true
$op->evaluate(new DateTime('2024-01-01'), new DateTime('2024-01-01')); // true
```

## Aliases
- `eq`, `equals`, `==`

## Notes
- For legacy support, `Xavante\Conditions\Operators\Equals` is a wrapper for this operator.

# GreaterThanOrEqual Operator

Checks if the first value is greater than or equal to the second. Supports numbers, strings, and dates.

## Usage
```php
use Xavante\Conditions\Operators\Comparison\GreaterThanOrEqual;

$op = new GreaterThanOrEqual();
$op->evaluate(5, 5); // true
$op->evaluate(6, 5); // true
$op->evaluate('b', 'a'); // true
```

## Aliases
- `gte`, `>=`

# LessThanOrEqual Operator

Checks if the first value is less than or equal to the second. Supports numbers, strings, and dates.

## Usage
```php
use Xavante\Conditions\Operators\Comparison\LessThanOrEqual;

$op = new LessThanOrEqual();
$op->evaluate(5, 5); // true
$op->evaluate(4, 5); // true
$op->evaluate('a', 'b'); // true
```

## Aliases
- `lte`, `<=`

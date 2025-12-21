# LessThanOrEqual Operator

Checks if the first value is less than or equal to the second. Supports numbers, strings, and dates.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::LESS_THAN_OR_EQUAL);
$op->evaluate(5, 5); // true
$op->evaluate(4, 5); // true
$op->evaluate('a', 'b'); // true
```

## Aliases
- `lte`, `<=`

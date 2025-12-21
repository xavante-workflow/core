# LessThan Operator

Checks if the first value is less than the second. Supports numbers, strings, and dates.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::LESS_THAN);
$op->evaluate(3, 5); // true
$op->evaluate('a', 'b'); // true
$op->evaluate(new DateTime('2023-12-31'), new DateTime('2024-01-01')); // true
```

## Aliases
- `lt`, `<`

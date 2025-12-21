# NotEquals Operator

Checks if two values are not equal. Supports numbers, strings, and dates.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::NOT_EQUALS);
$op->evaluate(5, 3); // true
$op->evaluate('foo', 'bar'); // true
$op->evaluate(new DateTime('2024-01-01'), new DateTime('2024-01-02')); // true
```

## Aliases
- `ne`, `!=`, `not_equals`

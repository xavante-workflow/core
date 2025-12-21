# LengthGreaterThan Operator

Checks if the string length is greater than the second value.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::LENGTH_GREATER_THAN);
$op->evaluate('foobar', 3); // true
$op->evaluate('foo', 5); // false
```

## Aliases
- `length_gt`, `length_greater_than`

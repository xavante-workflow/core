# IsNotEmpty Operator

Checks if a value is not empty using PHP's `empty()` logic.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_NOT_EMPTY);
$op->evaluate('foo', null); // true
$op->evaluate('', null); // false
```

## Aliases
- `not_empty`, `is_not_empty`

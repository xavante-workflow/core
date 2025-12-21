# IsNotNull Operator

Checks if a value is not null.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_NOT_NULL);
$op->evaluate('foo', null); // true
$op->evaluate(null, null); // false
```

## Aliases
- `is_not_null`, `not_null`

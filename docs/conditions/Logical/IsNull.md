# IsNull Operator

Checks if a value is null.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_NULL);
$op->evaluate(null, null); // true
$op->evaluate('foo', null); // false
```

## Aliases
- `is_null`, `null`

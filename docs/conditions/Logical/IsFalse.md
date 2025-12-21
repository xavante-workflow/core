# IsFalse Operator

Checks if a value is false (strict comparison).

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_FALSE);
$op->evaluate(false, null); // true
$op->evaluate(true, null); // false
```

## Aliases
- `is_false`, `false`

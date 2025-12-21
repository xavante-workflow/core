# IsTrue Operator

Checks if a value is true (strict comparison).

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_TRUE);
$op->evaluate(true, null); // true
$op->evaluate(false, null); // false
```

## Aliases
- `is_true`, `true`

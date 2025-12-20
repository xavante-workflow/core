# IsFalse Operator

Checks if a value is false (strict comparison).

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsFalse;

$op = new IsFalse();
$op->evaluate(false, null); // true
$op->evaluate(true, null); // false
```

## Aliases
- `is_false`, `false`

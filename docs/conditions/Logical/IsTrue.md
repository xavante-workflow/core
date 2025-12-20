# IsTrue Operator

Checks if a value is true (strict comparison).

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsTrue;

$op = new IsTrue();
$op->evaluate(true, null); // true
$op->evaluate(false, null); // false
```

## Aliases
- `is_true`, `true`

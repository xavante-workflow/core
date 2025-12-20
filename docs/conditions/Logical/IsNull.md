# IsNull Operator

Checks if a value is null.

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsNull;

$op = new IsNull();
$op->evaluate(null, null); // true
$op->evaluate('foo', null); // false
```

## Aliases
- `is_null`, `null`

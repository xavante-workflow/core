# IsNotNull Operator

Checks if a value is not null.

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsNotNull;

$op = new IsNotNull();
$op->evaluate('foo', null); // true
$op->evaluate(null, null); // false
```

## Aliases
- `is_not_null`, `not_null`

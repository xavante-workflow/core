# IsNotEmpty Operator

Checks if a value is not empty using PHP's `empty()` logic.

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsNotEmpty;

$op = new IsNotEmpty();
$op->evaluate('foo', null); // true
$op->evaluate('', null); // false
```

## Aliases
- `not_empty`, `is_not_empty`

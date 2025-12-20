# LengthEquals Operator

Checks if the string length equals the second value.

## Usage
```php
use Xavante\Conditions\Operators\String\LengthEquals;

$op = new LengthEquals();
$op->evaluate('foo', 3); // true
$op->evaluate('bar', 2); // false
```

## Aliases
- `length_eq`, `length_equals`

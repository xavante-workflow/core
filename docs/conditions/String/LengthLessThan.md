# LengthLessThan Operator

Checks if the string length is less than the second value.

## Usage
```php
use Xavante\Conditions\Operators\String\LengthLessThan;

$op = new LengthLessThan();
$op->evaluate('foo', 5); // true
$op->evaluate('foobar', 3); // false
```

## Aliases
- `length_lt`, `length_less_than`

# EndsWith Operator

Checks if the first string ends with the second string.

## Usage
```php
use Xavante\Conditions\Operators\String\EndsWith;

$op = new EndsWith();
$op->evaluate('foobar', 'bar'); // true
$op->evaluate('foo', 'bar'); // false
```

## Aliases
- `ends_with`, `endswith`

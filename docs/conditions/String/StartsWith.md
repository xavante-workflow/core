# StartsWith Operator

Checks if the first string starts with the second string.

## Usage
```php
use Xavante\Conditions\Operators\String\StartsWith;

$op = new StartsWith();
$op->evaluate('foobar', 'foo'); // true
$op->evaluate('barfoo', 'foo'); // false
```

## Aliases
- `starts_with`, `startswith`

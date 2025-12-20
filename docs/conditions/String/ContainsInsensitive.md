# ContainsInsensitive Operator

Checks if the first string contains the second string (case-insensitive).

## Usage
```php
use Xavante\Conditions\Operators\String\ContainsInsensitive;

$op = new ContainsInsensitive();
$op->evaluate('Hello World', 'world'); // true
$op->evaluate('abc', 'D'); // false
```

## Aliases
- `icontains`, `contains_insensitive`

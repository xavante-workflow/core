# ContainsInsensitive Operator

Checks if the first string contains the second string (case-insensitive).

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::CONTAINS_INSENSITIVE);
$op->evaluate('Hello World', 'world'); // true
$op->evaluate('abc', 'D'); // false
```

## Aliases
- `icontains`, `contains_insensitive`

# StartsWith Operator

Checks if the first string starts with the second string.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::STARTS_WITH);
$op->evaluate('foobar', 'foo'); // true
$op->evaluate('barfoo', 'foo'); // false
```

## Aliases
- `starts_with`, `startswith`

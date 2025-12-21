# EndsWith Operator

Checks if the first string ends with the second string.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::ENDS_WITH);
$op->evaluate('foobar', 'bar'); // true
$op->evaluate('foo', 'bar'); // false
```

## Aliases
- `ends_with`, `endswith`

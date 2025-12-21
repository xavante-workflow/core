# IsEmpty Operator

Checks if a value is empty using PHP's `empty()` logic. Returns true for null, '', 0, '0', false, [], etc.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_EMPTY);
$op->evaluate('', null); // true
$op->evaluate([], null); // true
$op->evaluate('foo', null); // false
```

## Aliases
- `empty`

## Notes
- The second argument is ignored.

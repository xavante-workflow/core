# IsEmpty Operator

Checks if a value is empty using PHP's `empty()` logic. Returns true for null, '', 0, '0', false, [], etc.

## Usage
```php
use Xavante\Conditions\Operators\Logical\IsEmpty;

$op = new IsEmpty();
$op->evaluate('', null); // true
$op->evaluate([], null); // true
$op->evaluate('foo', null); // false
```

## Aliases
- `empty`

## Notes
- The second argument is ignored.

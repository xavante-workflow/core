# Contains Operator

Checks if the first string contains the second string (case-sensitive).

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::CONTAINS);
$op->evaluate('hello world', 'world'); // true
$op->evaluate('abc', 'd'); // false
```

## Aliases
- `contains`

## Notes
- Both values are cast to string.
- An empty needle always returns true.

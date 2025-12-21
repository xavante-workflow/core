# GreaterThan Operator

Checks if the first value is greater than the second. Supports numbers, strings (lexicographic), and DateTime objects.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::GREATER_THAN);
$op->evaluate(10, 5); // true
$op->evaluate('b', 'a'); // true
$op->evaluate(new DateTime('tomorrow'), new DateTime('yesterday')); // true
```

## Aliases
- `gt`, `>`

## Notes
- Returns false if either value is null or not comparable.

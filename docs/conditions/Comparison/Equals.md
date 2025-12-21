# Equals Operator

Checks if two values are equal. Supports numbers, strings, and dates. This is the most common comparison operator.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::EQUALS);
$op->evaluate(5, 5); // true
$op->evaluate('foo', 'foo'); // true
$op->evaluate(new DateTime('tomorrow'), new DateTime('tomorrow')); // true
```

## Aliases
- `eq`, `equals`, `==`

## Notes
- For legacy support, `Xavante\Conditions\Operators\Equals` is a wrapper for this operator.

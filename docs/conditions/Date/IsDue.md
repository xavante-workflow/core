# IsDue Operator

Checks if a date/time value is due (less than or equal to now). Supports DateTime objects, timestamps, and date strings.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_DUE);
$op->evaluate('2024-01-01 00:00:00', null); // true if date is past or now
$op->evaluate(new DateTime('-1 day'), null); // true
```

## Aliases
- `is_due`, `due`

## Notes
- Returns false if the value cannot be parsed as a date.

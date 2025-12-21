# IsOverdue Operator

Checks if a date/time value is in the past (before now).

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_OVERDUE);
$op->evaluate('2024-01-01 00:00:00', null); // true if date is before now
```

## Aliases
- `is_overdue`, `overdue`

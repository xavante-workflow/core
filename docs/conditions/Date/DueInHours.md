# DueInHours Operator

Checks if a date is due in N hours from now.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::DUE_IN_HOURS);
$op->evaluate('2024-01-10 12:00:00', 3); // true if date is within 3 hours from now
```

## Aliases
- `due_in_hours`

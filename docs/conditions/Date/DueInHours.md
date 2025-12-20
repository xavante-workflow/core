# DueInHours Operator

Checks if a date is due in N hours from now.

## Usage
```php
use Xavante\Conditions\Operators\Date\DueInHours;

$op = new DueInHours();
$op->evaluate('2024-01-10 12:00:00', 3); // true if date is within 3 hours from now
```

## Aliases
- `due_in_hours`

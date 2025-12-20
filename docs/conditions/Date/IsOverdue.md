# IsOverdue Operator

Checks if a date/time value is in the past (before now).

## Usage
```php
use Xavante\Conditions\Operators\Date\IsOverdue;

$op = new IsOverdue();
$op->evaluate('2024-01-01 00:00:00', null); // true if date is before now
```

## Aliases
- `is_overdue`, `overdue`

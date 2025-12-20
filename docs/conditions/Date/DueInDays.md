# DueInDays Operator

Checks if a date is due in N days from now.

## Usage
```php
use Xavante\Conditions\Operators\Date\DueInDays;

$op = new DueInDays();
$op->evaluate('2024-01-10', 5); // true if date is within 5 days from now
```

## Aliases
- `due_in_days`

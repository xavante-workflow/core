# IsSameDay Operator

Checks if two date values are the same day.

## Usage
```php
use Xavante\Conditions\Operators\Date\IsSameDay;

$op = new IsSameDay();
$op->evaluate('2024-01-01', '2024-01-01'); // true
$op->evaluate('2024-01-01', '2024-01-02'); // false
```

## Aliases
- `is_same_day`, `same_day`

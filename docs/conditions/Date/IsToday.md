# IsToday Operator

Checks if a date value is today.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::IS_TODAY);
$op->evaluate('2024-01-01', null); // true if date is today
```

## Aliases
- `is_today`, `today`

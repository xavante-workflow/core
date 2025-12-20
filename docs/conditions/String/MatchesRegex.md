# MatchesRegex Operator

Checks if the first string matches the regex pattern in the second value.

## Usage
```php
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

$op = OperatorRegistry::get(OperatorConstants::MATCHES_REGEX);
$op->evaluate('hello123', '/[a-z]+[0-9]+/'); // true
$op->evaluate('hello', '/[0-9]+/'); // false
```

## Aliases
- `matches_regex`, `regex`

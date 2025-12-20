# SetVariableValueAction

The `SetVariableValueAction` sets a process variable to a specific value. This is useful for initializing, updating, or resetting workflow data.

## Features
- Sets a variable at a given path to a specified value
- Supports any value type (string, int, array, etc.)
- No configuration required after construction

## Basic Usage
```php
use Xavante\Actions\SetVariableValueAction;

$action = new SetVariableValueAction(
    'Set order status',
    'order.status',
    'approved'
);
$action->execute($process);
```

## Constructor Parameters
- `description` (string|null): Optional description for the action
- `variablePath` (string): Path of the variable to set
- `value` (mixed): Value to assign to the variable

## Example
```php
$action = new SetVariableValueAction(null, 'user.active', true);
$action->execute($process);
```

## Output
- The variable at `variablePath` is set to `value` in the process variables.

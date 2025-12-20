# CopyVariableAction

The `CopyVariableAction` copies the value from one process variable to another. This is useful for data mapping, workflow branching, or variable aliasing.

## Features
- Copies the value from a source variable path to a target variable path
- Supports deep variable paths (e.g., `order.customer.id`)
- No configuration required after construction

## Basic Usage
```php
use Xavante\Actions\CopyVariableAction;

$action = new CopyVariableAction(
    'Copy order ID',
    'order.id',
    'invoice.order_id'
);
$action->execute($process);
```

## Constructor Parameters
- `description` (string|null): Optional description for the action
- `sourceVariablePath` (string): Path of the variable to copy from
- `targetVariablePath` (string): Path of the variable to copy to

## Example
```php
$action = new CopyVariableAction(null, 'user.email', 'notification.recipient');
$action->execute($process);
```

## Output
- The value at `sourceVariablePath` is copied to `targetVariablePath` in the process variables.

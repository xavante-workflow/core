# AddLogEntryAction

The `AddLogEntryAction` appends a message to the process audit log/history. This is useful for tracking workflow progress, debugging, or recording business events.

## Features
- Appends a custom message to the process history (audit log)
- Can be used for audit trails, debugging, or business event tracking

## Basic Usage
```php
use Xavante\Actions\AddLogEntryAction;

$action = new AddLogEntryAction('Order created');
$action->execute($process);
```

## Configuration
No configuration is required for this action. The message is set via the constructor.

## Example
```php
$action = new AddLogEntryAction('Step completed');
$action->execute($process);
```

## Output
- The message is added to the process history with the type 'audit'.

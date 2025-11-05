# README — Approval with Timers (Reminders, Escalation, Auto-Expire)

## Story: The approval queue that never gets emptied
A procurement team receives dozens of approvals daily. Approvers often forget to act. Some requests linger for weeks, blocking budgets and vendor onboarding. Manual chasing is error-prone; there's no SLA, no escalation, and no consistent reminders.

## Solution: Timed reminders, escalation, and auto-expiration
This workflow augments the simple approval with sophisticated timer-based behaviors:
- **Periodic reminders** while awaiting approval (configurable intervals, default 24h)
- **Escalation to backup approver** if the SLA window is exceeded (default 48h)
- **Auto-expiration** after a hard deadline (default 7 days)
- **Comprehensive audit trail** with state transitions and timer events
- **Robust error handling** for invalid timer configurations and edge cases

## When to use it
- Approvals must meet an SLA (e.g., approve within 48 hours)
- Automated reminders reduce cycle time and prevent stalled approvals
- You need time-bound behavior even if nobody touches the system
- Complex approval hierarchies requiring escalation to backup approvers
- Audit compliance requiring detailed approval timeline trackingoval with Timers (Reminders, Escalation, Auto-Expire)

## Story: The approval queue that never gets emptied
A procurement team receives dozens of approvals daily. Approvers often forget to act. Some requests linger for weeks, blocking budgets and vendor onboarding. Manual chasing is error-prone; there’s no SLA, no escalation, and no consistent reminders.

## Solution: Timed reminders, escalation, and auto-expiration
This workflow augments the simple approval with timers:
- Periodic reminders while awaiting approval.
- Escalation to a backup approver if the SLA window is exceeded.
- Auto-expire requests after a hard deadline.

## When to use it
- Approvals must meet an SLA (e.g., approve within 48 hours).
- Automated reminders reduce cycle time.
- You need time-bound behavior even if nobody touches the system.

## State Model

### States
1. **Draft** (initial)
   - Where approval requests are composed and prepared
   - No timer behavior active

2. **PendingApproval** (primary approval state)
   - **Entry Actions**: 
     - Assign reviewer via HTTP API call
     - Schedule all timer events (reminders, escalation, expiration)
   - **Exit Actions**:
     - Cancel all active timers 
     - Record audit trail for state exit
   - **Timer Events**: Configured via variables (see Variables section)

3. **Escalated** (backup approver takes over)
   - **Entry Actions**:
     - Send escalation notifications via HTTP API
     - Reassign current approver to backup approver
   - **Timer Events**: Continue expiration countdown
   - Can transition to Approved, Rejected, or AutoExpired

4. **Terminal States**:
   - **Approved**: Successful approval completion
   - **Rejected**: Explicit rejection by approver
   - **AutoExpired**: Automatic expiration after deadline

### Events
- **User Events**:
  - `submit`: Start approval process (Draft → PendingApproval)
  - `approve`: Explicit approval (→ Approved)
  - `reject`: Explicit rejection (→ Rejected)

- **System Timer Events**:
  - `reminderTick`: Periodic reminders (stays in current state)
  - `escalate`: SLA breach escalation (PendingApproval → Escalated)
  - `expire`: Hard deadline expiration (→ AutoExpired)

### Variables
The workflow uses configurable variables for flexible timer behavior:

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `request.status` | string | 'draft' | Controls state transitions |
| `approver` | string | '' | Primary approver identifier |
| `backupApprover` | string | '' | Escalation target approver |
| `current.approver` | string | '' | Currently assigned approver |
| `amount` | number | 0 | Request amount for routing logic |
| `sla.hours` | number | 48 | Hours until escalation |
| `reminder.hours` | number | 24 | Reminder interval in hours |
| `expiry.days` | number | 7 | Days until auto-expiration |
| `timers.scheduled` | string | 'false' | Timer scheduling status |
| `timers.cancelled` | string | 'false' | Timer cancellation status |
| `audit.log` | string | '' | Audit trail accumulator |

## Reference Process Example
```php
// Creating a new approval process with timer configuration
$process = $workflow->createProcess();

// Configure timer parameters
$process->setVariableValue('approver', 'manager@company.com');
$process->setVariableValue('backupApprover', 'director@company.com');
$process->setVariableValue('amount', 15000);
$process->setVariableValue('sla.hours', 24);        // Escalate after 24h
$process->setVariableValue('reminder.hours', 8);    // Remind every 8h
$process->setVariableValue('expiry.days', 3);       // Expire after 3 days

// Start the approval process
$process->setVariableValue('request.status', 'submitted');
$processor->process($process);

// Process will be in PendingApproval state with timers scheduled
```

## Process Instance JSON Structure
```json
{
  "workflowId": "approval-with-timers-v1",
  "instanceId": "process-uuid-here",
  "activeStatesIds": ["id:pending-approval"],
  "variables": {
    "request.status": "submitted",
    "approver": "manager@company.com",
    "backupApprover": "director@company.com", 
    "current.approver": "manager@company.com",
    "amount": 15000,
    "sla.hours": 24,
    "reminder.hours": 8,
    "expiry.days": 3,
    "timers.scheduled": "true",
    "timers.cancelled": "false",
    "audit.log": "Process started - Timers scheduled"
  },
  "history": [
    {"event": "submit", "timestamp": "2025-11-01T10:00:00Z"},
    {"action": "assignReviewer", "timestamp": "2025-11-01T10:00:01Z"},
    {"action": "scheduleTimers", "timestamp": "2025-11-01T10:00:02Z"}
  ]
}
```

## Timer Semantics and Behavior

### Timer Event Prioritization
The workflow engine uses deterministic transition selection when multiple timer events occur:
1. **Expiration** (highest priority) - hard deadline takes precedence
2. **Escalation** - SLA breach escalation 
3. **Reminder** (lowest priority) - gentle notifications

### Timer Validation and Edge Cases
- **Negative Values**: Invalid timer values (≤ 0) are handled gracefully - no events trigger
- **Boundary Conditions**: When reminder.hours = sla.hours = expiry.hours, expiration takes precedence
- **Zero Intervals**: Timer events are disabled for zero or negative intervals
- **Large Values**: Supports very large timer values (up to PHP_INT_MAX)

### State Transition Rules
- **reminderTick**: Stays in current state, sends notification (HTTP API call)
- **escalate**: PendingApproval → Escalated + reassign to backup approver
- **expire**: Any state → AutoExpired (terminal)
- **User actions** (approve/reject): Override timer events, cancel all pending timers

## Implementation Details

### Actions and Integrations
- **HTTP Actions**: All external integrations use `MakeHttpRequestAction` with dry_run support
- **Variable Management**: Uses `SetVariableValueAction` and `CopyVariableAction` for state management  
- **Audit Trail**: Automatic logging of state transitions and timer events

### Testing Framework
The implementation includes comprehensive test coverage:

#### Core Test Suites
1. **ExecutionApprovalWithTimerTest** (9 tests)
   - Happy path scenarios (immediate approval/rejection)
   - Timer-driven workflows (reminders, escalation, expiration)
   - User intervention during timer sequences
   - Complete simulation scenarios

2. **TimerSystemTest** (11 tests)
   - Timer scheduling and cancellation
   - Multiple reminder cycles
   - Custom timer interval configurations
   - Edge cases and boundary conditions

3. **EscalationScenarioTest** (13 tests)
   - Basic and complex escalation flows
   - Backup approver scenarios
   - High-value request routing
   - Business rule enforcement

4. **EdgeCasesAndErrorsTest** (13 tests)
   - Invalid timer configurations
   - Malformed variable types
   - Concurrent event handling
   - Resource constraints and recovery

### Test Statistics
- **Total Tests**: 46 tests across 4 test suites
- **Assertions**: 150+ comprehensive validations
- **Coverage**: State transitions, timer logic, error handling, edge cases
- **Execution Time**: ~0.5 seconds for complete suite

## Operational Guidelines

### Deployment Considerations
- **Timer Persistence**: Ensure timer scheduling system survives application restarts
- **Dry Run Mode**: All HTTP actions support dry_run for testing environments
- **Error Handling**: Invalid configurations fail gracefully without disrupting workflow

### Configuration Best Practices
- Set realistic SLA times based on organizational response capabilities
- Configure reminder intervals shorter than escalation timeouts
- Use backup approvers from different organizational levels
- Monitor audit logs for compliance and performance analysis

### Integration Requirements
- External timer scheduling system (cron, job queue, etc.)
- HTTP API endpoints for notifications and approver assignments
- Audit/logging infrastructure for compliance tracking

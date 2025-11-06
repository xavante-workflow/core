# Approval with Timer - Implementation Guide

## Quick Start

### 1. Basic Workflow Setup

```php
<?php
// Load the workflow definition
$workflow = require 'path/to/2-approval-with-timer.php';

// Create a processor with timer context
$processor = new \Xavante\Runtime\Processor([
    'timer_system' => true,
    'dry_run' => false  // Set to true for testing
]);

// Create a new approval process
$process = $workflow->createProcess();
```

### 2. Configure Approval Parameters

```php
// Set basic approval configuration
$process->setVariableValue('approver', 'manager@company.com');
$process->setVariableValue('backupApprover', 'director@company.com');
$process->setVariableValue('amount', 15000);

// Configure timer behavior (optional - uses defaults if not set)
$process->setVariableValue('sla.hours', 24);        // Escalate after 24h
$process->setVariableValue('reminder.hours', 8);    // Remind every 8h  
$process->setVariableValue('expiry.days', 3);       // Expire after 3 days
```

### 3. Start the Approval Process

```php
// Submit the request for approval
$process->setVariableValue('request.status', 'submitted');
$processor->process($process);

// Process is now in PendingApproval state with timers scheduled
echo "Process state: " . $process->getActiveStatesIds()[0]; // id:pending-approval
echo "Timers scheduled: " . $process->getVariableValue('timers.scheduled'); // true
```

## Configuration Options

### Timer Configuration Variables

| Variable | Type | Default | Description | Range |
|----------|------|---------|-------------|-------|
| `sla.hours` | number | 48 | Hours before escalation | 1+ |
| `reminder.hours` | number | 24 | Hours between reminders | 1+ |  
| `expiry.days` | number | 7 | Days before auto-expiration | 1+ |

### Approver Configuration

```php
// Single approver (no escalation)
$process->setVariableValue('approver', 'single.approver@company.com');
// Leave backupApprover empty - escalation will still occur but without reassignment

// Two-level approval hierarchy  
$process->setVariableValue('approver', 'manager@company.com');
$process->setVariableValue('backupApprover', 'director@company.com');

// Complex hierarchy (use variables for dynamic assignment)
$process->setVariableValue('approver', getApproverForAmount($amount));
$process->setVariableValue('backupApprover', getBackupApproverForAmount($amount));
```

### Amount-Based Routing

```php
function getApprovalConfig($amount) {
    if ($amount < 1000) {
        return [
            'approver' => 'supervisor@company.com',
            'backupApprover' => 'manager@company.com',
            'sla.hours' => 8,
            'reminder.hours' => 2
        ];
    } elseif ($amount < 10000) {
        return [
            'approver' => 'manager@company.com', 
            'backupApprover' => 'director@company.com',
            'sla.hours' => 24,
            'reminder.hours' => 6
        ];
    } else {
        return [
            'approver' => 'director@company.com',
            'backupApprover' => 'vice.president@company.com',
            'sla.hours' => 48,
            'reminder.hours' => 12
        ];
    }
}

// Apply configuration
$config = getApprovalConfig($requestAmount);
foreach ($config as $key => $value) {
    $process->setVariableValue($key, $value);
}
```

## Event Handling

### User-Initiated Events

```php
// Approve the request
$process->setVariableValue('request.status', 'approved');
$processor->process($process);
// Result: Process moves to Approved state, timers cancelled

// Reject the request  
$process->setVariableValue('request.status', 'rejected');
$processor->process($process);
// Result: Process moves to Rejected state, timers cancelled
```

### System Timer Events

```php
// Simulate timer events (in production, these come from external scheduler)
class TimerScheduler {
    public function processTimerEvents(Process $process, int $hoursElapsed) {
        $reminderInterval = $process->getVariableValue('reminder.hours');
        $slaHours = $process->getVariableValue('sla.hours');
        $expiryHours = $process->getVariableValue('expiry.days') * 24;
        
        // Check for reminder
        if ($hoursElapsed % $reminderInterval === 0 && $hoursElapsed < $slaHours) {
            // Send reminder notification (HTTP action will handle this)
            // No state change needed for reminders
        }
        
        // Check for escalation
        if ($hoursElapsed >= $slaHours && $hoursElapsed < $expiryHours) {
            $process->setVariableValue('request.status', 'escalated');
            $processor->process($process);
        }
        
        // Check for expiration
        if ($hoursElapsed >= $expiryHours) {
            $process->setVariableValue('request.status', 'expired');
            $processor->process($process);
        }
    }
}
```

## Integration Patterns

### HTTP Action Configuration

The workflow uses HTTP actions for external integrations. Configure these for your environment:

```php
// In production, modify the workflow definition or use configuration injection
class ProductionWorkflowFactory {
    public static function createApprovalWorkflow(array $config) {
        $workflow = require '2-approval-with-timer.php';
        
        // Override HTTP action URLs for production
        foreach ($workflow->getStates() as $state) {
            foreach ($state->getEntryActions() as $action) {
                if ($action instanceof MakeHttpRequestAction) {
                    $action->configure([
                        'url' => $config['api_base_url'] . '/notify-approver',
                        'dry_run' => false
                    ]);
                }
            }
        }
        
        return $workflow;
    }
}
```

### Database Integration

```php
class ApprovalProcessRepository {
    public function save(Process $process): void {
        $data = [
            'process_id' => $process->getId(),
            'workflow_id' => $process->getWorkflowId(), 
            'current_state' => $process->getActiveStatesIds()[0],
            'variables' => json_encode($process->getVariables()),
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ];
        
        $this->database->insert('approval_processes', $data);
    }
    
    public function load(string $processId): Process {
        $row = $this->database->selectOne('approval_processes', ['process_id' => $processId]);
        
        $workflow = WorkflowFactory::createApprovalWorkflow();
        $process = $workflow->createProcess($processId);
        
        // Restore state
        $process->setActiveStatesIds([$row['current_state']]);
        
        // Restore variables
        $variables = json_decode($row['variables'], true);
        foreach ($variables as $key => $value) {
            $process->setVariableValue($key, $value);
        }
        
        return $process;
    }
}
```

### Timer Scheduler Integration

```php
// Using a job queue system (Laravel Horizon, Symfony Messenger, etc.)
class TimerScheduler {
    public function scheduleTimers(Process $process): void {
        $processId = $process->getId();
        $reminderHours = $process->getVariableValue('reminder.hours');
        $slaHours = $process->getVariableValue('sla.hours');
        $expiryHours = $process->getVariableValue('expiry.days') * 24;
        
        // Schedule reminder jobs
        for ($h = $reminderHours; $h < $slaHours; $h += $reminderHours) {
            $this->jobQueue->schedule(
                new ReminderJob($processId),
                now()->addHours($h)
            );
        }
        
        // Schedule escalation job
        $this->jobQueue->schedule(
            new EscalationJob($processId),
            now()->addHours($slaHours)
        );
        
        // Schedule expiration job
        $this->jobQueue->schedule(
            new ExpirationJob($processId),
            now()->addHours($expiryHours)
        );
    }
    
    public function cancelTimers(string $processId): void {
        $this->jobQueue->cancelJobsForProcess($processId);
    }
}

class ReminderJob {
    public function handle() {
        // Send reminder notification
        // No process state change needed
    }
}

class EscalationJob { 
    public function handle() {
        $process = $this->processRepository->load($this->processId);
        if ($process->getActiveStatesIds()[0] === 'id:pending-approval') {
            $process->setVariableValue('request.status', 'escalated');
            $this->processor->process($process);
            $this->processRepository->save($process);
        }
    }
}
```

## Error Handling

### Configuration Validation

```php
class ApprovalConfigValidator {
    public static function validate(Process $process): array {
        $errors = [];
        
        // Required fields
        if (empty($process->getVariableValue('approver'))) {
            $errors[] = 'Approver is required';
        }
        
        // Timer validation  
        $slaHours = $process->getVariableValue('sla.hours');
        $reminderHours = $process->getVariableValue('reminder.hours');
        $expiryDays = $process->getVariableValue('expiry.days');
        
        if ($slaHours <= 0) {
            $errors[] = 'SLA hours must be positive';
        }
        
        if ($reminderHours <= 0) {
            $errors[] = 'Reminder hours must be positive';
        }
        
        if ($reminderHours >= $slaHours) {
            $errors[] = 'Reminder interval must be less than SLA timeout';
        }
        
        if ($expiryDays <= 0) {
            $errors[] = 'Expiry days must be positive';
        }
        
        return $errors;
    }
}

// Usage
$errors = ApprovalConfigValidator::validate($process);
if (!empty($errors)) {
    throw new InvalidApprovalConfigException(implode('; ', $errors));
}
```

### Runtime Error Recovery

```php
class ApprovalProcessManager {
    public function processApproval(Process $process): ProcessResult {
        try {
            $this->processor->process($process);
            $this->repository->save($process);
            
            return ProcessResult::success($process);
            
        } catch (TimerSchedulingException $e) {
            // Timer system failure - continue without timers
            $process->setVariableValue('timers.scheduled', 'failed');
            $this->repository->save($process);
            
            return ProcessResult::warning($process, 'Timers could not be scheduled');
            
        } catch (NotificationException $e) {
            // Notification failure - continue process
            $process->setVariableValue('audit.log', 
                $process->getVariableValue('audit.log') . '; Notification failed: ' . $e->getMessage()
            );
            
            return ProcessResult::warning($process, 'Notification failed');
            
        } catch (Exception $e) {
            // Unexpected error - rollback
            $this->repository->rollback($process);
            
            return ProcessResult::error($process, $e->getMessage());
        }
    }
}
```

## Monitoring and Observability

### Audit Trail Analysis

```php
class ApprovalAnalytics {
    public function getProcessMetrics(string $processId): array {
        $process = $this->repository->load($processId);
        
        return [
            'process_id' => $processId,
            'current_state' => $process->getActiveStatesIds()[0],
            'created_at' => $this->getProcessCreationTime($process),
            'time_in_pending' => $this->getTimeInState($process, 'id:pending-approval'),
            'time_in_escalated' => $this->getTimeInState($process, 'id:escalated'),
            'reminders_sent' => $this->countRemindersSent($process),
            'escalated' => $this->hasBeenEscalated($process),
            'approver' => $process->getVariableValue('approver'),
            'current_approver' => $process->getVariableValue('current.approver'),
            'amount' => $process->getVariableValue('amount')
        ];
    }
    
    public function getWorkflowKPIs(): array {
        return [
            'avg_approval_time' => $this->calculateAverageApprovalTime(),
            'escalation_rate' => $this->calculateEscalationRate(), 
            'expiration_rate' => $this->calculateExpirationRate(),
            'sla_compliance' => $this->calculateSLACompliance(),
            'reminder_effectiveness' => $this->calculateReminderEffectiveness()
        ];
    }
}
```

### Performance Monitoring

```php
class ApprovalPerformanceMonitor {
    public function trackProcessExecution(Process $process): void {
        $startTime = microtime(true);
        
        $this->processor->process($process);
        
        $executionTime = microtime(true) - $startTime;
        
        $this->metrics->record('approval.process.execution_time', $executionTime, [
            'state' => $process->getActiveStatesIds()[0],
            'has_timers' => $process->getVariableValue('timers.scheduled') === 'true'
        ]);
    }
}
```

## Production Deployment

### Environment Configuration

```php
// config/workflows.php
return [
    'approval_with_timer' => [
        'api_endpoints' => [
            'assign_reviewer' => env('APPROVAL_API_ASSIGN_REVIEWER'),
            'notify_escalation' => env('APPROVAL_API_NOTIFY_ESCALATION'), 
            'send_reminder' => env('APPROVAL_API_SEND_REMINDER')
        ],
        'default_timers' => [
            'sla_hours' => env('APPROVAL_DEFAULT_SLA_HOURS', 48),
            'reminder_hours' => env('APPROVAL_DEFAULT_REMINDER_HOURS', 24),
            'expiry_days' => env('APPROVAL_DEFAULT_EXPIRY_DAYS', 7)
        ],
        'timer_scheduler' => env('APPROVAL_TIMER_SCHEDULER', 'database'),
        'dry_run' => env('APPROVAL_DRY_RUN', false)
    ]
];
```

### Health Checks

```php
class ApprovalWorkflowHealthCheck {
    public function check(): HealthStatus {
        try {
            // Test workflow loading
            $workflow = WorkflowFactory::createApprovalWorkflow();
            
            // Test process creation
            $testProcess = $workflow->createProcess();
            
            // Test configuration
            ApprovalConfigValidator::validate($testProcess);
            
            // Test timer system
            $this->timerScheduler->testConnection();
            
            // Test external APIs  
            $this->httpClient->testEndpoints();
            
            return HealthStatus::healthy();
            
        } catch (Exception $e) {
            return HealthStatus::unhealthy($e->getMessage());
        }
    }
}
```

This implementation guide provides comprehensive coverage for deploying and operating the Approval with Timer workflow in production environments.
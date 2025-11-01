<?php

namespace Tests\Integration\UseCases\ApprovalWithTimer;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Runtime\Process;
use Xavante\Runtime\Processor;

/**
 * Trait providing helper methods and shared state for approval with timer workflow testing.
 * 
 * This trait encapsulates the common workflow execution patterns used across
 * multiple test scenarios for the time-based approval workflow with reminders,
 * escalation, and auto-expiration features.
 */
trait ApprovalWithTimerHelper {

    /** @var Workflow The workflow definition loaded from 2-approval-with-timer.php */
    protected static Workflow $workflow;
    
    /** @var Process|null The current workflow process instance */
    protected static ?Process $process = null;
    
    /** @var Processor The workflow execution engine */
    protected static Processor $processor;

    /**
     * Initialize the workflow definition and processor.
     * Called once per test class to set up the testing environment.
     */
    protected static function initialize(): void
    {
        // Load the approval with timer workflow definition
        self::$workflow = require __DIR__ . '/2-approval-with-timer.php';
        
        // Create processor with timer context for simulating time-based events
        $context = [
            'timer_system' => true,
            'dry_run' => true // Ensure all HTTP actions run in dry mode
        ];
        self::$processor = new Processor($context);

        self::$process = null;
    }

    /**
     * Create a new process instance from the workflow definition.
     * The process starts in the initial state (draft).
     */
    protected function doInstantiateProcess(): void
    {
        self::$process = self::$processor->instantiate(self::$workflow);
    }

    /**
     * Set up a typical approval request with default values.
     * 
     * @param array $overrides Override default values (approver, backupApprover, amount, etc.)
     */
    protected function setupApprovalRequest(array $overrides = []): void
    {
        $defaults = [
            'approver' => 'manager@company.com',
            'backupApprover' => 'director@company.com', 
            'amount' => 5000,
            'sla.hours' => 48,
            'reminder.hours' => 24,
            'expiry.days' => 7
        ];

        $values = array_merge($defaults, $overrides);

        foreach ($values as $variable => $value) {
            self::$process->setVariableValue($variable, $value);
        }
    }

    /**
     * Trigger the submit event to start the approval process.
     * Sets request.status = 'submitted' and transitions to pending-approval.
     */
    protected function triggerSubmitEvent(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:submit');
        return 'id:submit';
    }

    /**
     * Trigger the approve event.
     * Sets request.status = 'approved' and transitions to approved state.
     */
    protected function triggerApproveEvent(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:approve');
        return 'id:approve';
    }

    /**
     * Trigger the reject event.
     * Sets request.status = 'rejected' and transitions to rejected state.
     */
    protected function triggerRejectEvent(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:reject');
        return 'id:reject';
    }

    /**
     * Simulate a reminder tick (system-generated event).
     * This would normally be triggered by a timer system.
     */
    protected function triggerReminderTick(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:reminderTick');
        return 'id:reminderTick';
    }

    /**
     * Simulate SLA escalation (system-generated event).
     * Sets request.status = 'escalated' and transitions to escalated state.
     */
    protected function triggerEscalateEvent(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:escalate');
        return 'id:escalate';
    }

    /**
     * Simulate auto-expiration (system-generated event).
     * Sets request.status = 'expired' and transitions to auto-expired state.
     */
    protected function triggerExpireEvent(): string
    {
        self::$processor->triggerEvent(self::$process, 'id:expire');
        return 'id:expire';
    }

    /**
     * Execute workflow processing cycle.
     * 
     * This evaluates all possible transitions from current active states:
     * - Checks transition conditions
     * - Executes state transitions if conditions are met (deterministic selection)
     * - Runs entry/exit actions for states
     * - Updates process state and history
     */
    protected function doProcessorProcess(): void 
    {
        self::$processor->process(self::$process);
    }

    /**
     * Get current active state IDs.
     * 
     * @return array Current active state IDs
     */
    protected function getActiveStates(): array
    {
        return self::$process->getActiveStatesIds();
    }

    /**
     * Get current variable value.
     * 
     * @param string $variableName Variable name to retrieve
     * @return mixed Variable value
     */
    protected function getVariableValue(string $variableName): mixed
    {
        return self::$process->getVariableValue($variableName);
    }

    /**
     * Set variable value.
     * 
     * @param string $variableName Variable name to set
     * @param mixed $value Value to set
     */
    protected function setVariableValue(string $variableName, mixed $value): void
    {
        self::$process->setVariableValue($variableName, $value);
    }

    /**
     * Assert process is in expected state.
     * 
     * @param string $expectedState Expected state ID
     * @param string $message Optional assertion message
     */
    protected function assertProcessInState(string $expectedState, string $message = ''): void
    {
        $activeStates = $this->getActiveStates();
        $this->assertEquals([$expectedState], $activeStates, $message ?: "Process should be in state: $expectedState");
    }

    /**
     * Assert variable has expected value.
     * 
     * @param string $variableName Variable name
     * @param mixed $expectedValue Expected value
     * @param string $message Optional assertion message
     */
    protected function assertVariableEquals(string $variableName, mixed $expectedValue, string $message = ''): void
    {
        $actualValue = $this->getVariableValue($variableName);
        $this->assertEquals($expectedValue, $actualValue, $message ?: "Variable $variableName should equal $expectedValue");
    }

    /**
     * Simulate the passage of time for timer-based testing.
     * In a real system, this would be handled by an external timer/scheduler.
     * 
     * @param int $hours Hours to simulate
     * @return array Events that would be triggered at this time
     */
    protected function simulateTimePassage(int $hours): array
    {
        $eventsTriggered = [];
        $reminderInterval = $this->getVariableValue('reminder.hours');
        $slaHours = $this->getVariableValue('sla.hours');
        $expiryDays = $this->getVariableValue('expiry.days');
        $expiryHours = $expiryDays * 24;

        // Guard against negative or zero values - they should not trigger events
        if ($reminderInterval <= 0 || $slaHours <= 0 || $expiryDays <= 0) {
            return $eventsTriggered; // Return empty array for invalid timer configurations
        }

        // Check if we should trigger reminder (every reminder.hours)
        if ($hours > 0 && $hours % $reminderInterval === 0 && $hours < $slaHours) {
            $eventsTriggered[] = 'reminderTick';
        }

        // Check if we should trigger escalation (at sla.hours)
        if ($hours >= $slaHours && $hours < $expiryHours) {
            $eventsTriggered[] = 'escalate';
        }

        // Check if we should trigger expiration (at expiry.days * 24)
        if ($hours >= $expiryHours) {
            $eventsTriggered[] = 'expire';
        }

        return $eventsTriggered;
    }

    /**
     * Execute a complete timer simulation scenario.
     * 
     * @param int $totalHours Total hours to simulate
     * @param array $userActions User actions to perform at specific hours [hour => action]
     * @return array Complete execution log with times, events, and states
     */
    protected function executeTimerScenario(int $totalHours, array $userActions = []): array
    {
        $log = [];
        
        for ($hour = 0; $hour <= $totalHours; $hour++) {
            // Log current state at this hour
            $currentState = $this->getActiveStates()[0] ?? 'unknown';
            $log[$hour]['initial_state'] = $currentState;

            // Check for user actions at this hour
            if (isset($userActions[$hour])) {
                $action = $userActions[$hour];
                switch ($action) {
                    case 'approve':
                        $this->triggerApproveEvent();
                        $log[$hour]['user_action'] = 'approve';
                        break;
                    case 'reject':
                        $this->triggerRejectEvent();
                        $log[$hour]['user_action'] = 'reject';
                        break;
                }
            }

            // Check for timer events
            $timerEvents = $this->simulateTimePassage($hour);
            foreach ($timerEvents as $event) {
                switch ($event) {
                    case 'reminderTick':
                        $this->triggerReminderTick();
                        $log[$hour]['timer_events'][] = 'reminderTick';
                        break;
                    case 'escalate':
                        $this->triggerEscalateEvent();
                        $log[$hour]['timer_events'][] = 'escalate';
                        break;
                    case 'expire':
                        $this->triggerExpireEvent();
                        $log[$hour]['timer_events'][] = 'expire';
                        break;
                }
            }

            // Process workflow after events
            $this->doProcessorProcess();
            
            // Log final state after processing
            $finalState = $this->getActiveStates()[0] ?? 'unknown';
            $log[$hour]['final_state'] = $finalState;

            // Stop if we reached a terminal state
            if (in_array($finalState, ['id:approved', 'id:rejected', 'id:auto-expired'])) {
                $log[$hour]['terminal'] = true;
                break;
            }
        }

        return $log;
    }
}
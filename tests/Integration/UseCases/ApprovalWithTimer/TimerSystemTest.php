<?php

namespace Tests\Integration\UseCases\ApprovalWithTimer;

use PHPUnit\Framework\TestCase;

/**
 * Specialized test class for timer system behavior in the approval workflow.
 * 
 * This test class focuses specifically on the timing aspects of the workflow:
 * - Timer scheduling and cancellation
 * - Time-based event generation (reminders, escalation, expiration)
 * - Timer system integration with workflow state management
 * - Edge cases in timer handling
 */
class TimerSystemTest extends TestCase
{
    use ApprovalWithTimerHelper;

    public function setUp(): void
    {
        static::initialize();
    }

    /**
     * Test that timers are properly scheduled when entering PendingApproval state.
     */
    public function testTimerSchedulingOnEntry(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 12,  // Custom reminder interval
            'sla.hours' => 24,       // Custom SLA
            'expiry.days' => 3       // Custom expiry
        ]);

        // Initially no timers should be scheduled
        $this->assertVariableEquals('timers.scheduled', 'false', 'No timers should be scheduled initially');

        // Submit to trigger entry into PendingApproval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Verify entry action executed - timers should be scheduled
        $this->assertVariableEquals('timers.scheduled', 'true', 'Timers should be scheduled on entry to PendingApproval');
        
        // Verify we have the correct timer configuration
        $this->assertVariableEquals('reminder.hours', 12, 'Reminder interval should be set');
        $this->assertVariableEquals('sla.hours', 24, 'SLA hours should be set');
        $this->assertVariableEquals('expiry.days', 3, 'Expiry days should be set');
    }

    /**
     * Test that timers are properly cancelled when exiting PendingApproval state.
     */
    public function testTimerCancellationOnExit(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to PendingApproval state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertVariableEquals('timers.scheduled', 'true');

        // Exit via approval
        $this->triggerApproveEvent();
        $this->doProcessorProcess();

        // Verify exit action executed - timers should be cancelled
        $this->assertVariableEquals('timers.cancelled', 'true', 'Timers should be cancelled on exit from PendingApproval');
        $this->assertProcessInState('id:approved');
    }

    /**
     * Test timer cancellation on rejection path.
     */
    public function testTimerCancellationOnRejection(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to PendingApproval state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertVariableEquals('timers.scheduled', 'true');

        // Exit via rejection
        $this->triggerRejectEvent();
        $this->doProcessorProcess();

        // Verify timers cancelled on rejection too
        $this->assertVariableEquals('timers.cancelled', 'true', 'Timers should be cancelled on rejection');
        $this->assertProcessInState('id:rejected');
    }

    /**
     * Test timer cancellation on escalation (when moving to Escalated state).
     */
    public function testTimerCancellationOnEscalation(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to PendingApproval state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertVariableEquals('timers.scheduled', 'true');

        // Trigger escalation
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Verify timers cancelled when escalating
        $this->assertVariableEquals('timers.cancelled', 'true', 'Timers should be cancelled when escalating');
        $this->assertProcessInState('id:escalated');
    }

    /**
     * Test reminder tick behavior - should not change state but execute actions.
     */
    public function testReminderTickBehavior(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to PendingApproval state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Clear audit log to test reminder action
        $this->setVariableValue('audit.log', '');

        // Trigger reminder tick
        $this->triggerReminderTick();
        $this->doProcessorProcess();

        // Should remain in same state but reminder action should execute
        $this->assertProcessInState('id:pending-approval', 'Reminder should not change state');
        $this->assertVariableEquals('audit.log', 'Reminder sent', 'Reminder action should execute');
    }

    /**
     * Test multiple reminder ticks over time.
     */
    public function testMultipleReminderTicks(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest(['reminder.hours' => 8]); // More frequent reminders for testing

        // Get to PendingApproval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        $reminderCount = 0;

        // Simulate multiple reminder periods (every 8 hours for 32 hours = 4 reminders)
        for ($hour = 8; $hour <= 32; $hour += 8) {
            $events = $this->simulateTimePassage($hour);
            
            if (in_array('reminderTick', $events)) {
                $this->triggerReminderTick();
                $this->doProcessorProcess();
                $reminderCount++;
                
                // Should still be in pending approval
                $this->assertProcessInState('id:pending-approval', "Should remain in pending after reminder $reminderCount");
            }
        }

        $this->assertGreaterThan(0, $reminderCount, 'Should have sent at least one reminder');
    }

    /**
     * Test timer system with custom intervals.
     */
    public function testCustomTimerIntervals(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 6,   // Every 6 hours
            'sla.hours' => 18,       // Escalate at 18 hours  
            'expiry.days' => 1       // Expire after 1 day (24 hours)
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Test reminder timing
        $events6h = $this->simulateTimePassage(6);
        $this->assertContains('reminderTick', $events6h, 'Should have reminder at 6 hours');

        $events12h = $this->simulateTimePassage(12);
        $this->assertContains('reminderTick', $events12h, 'Should have reminder at 12 hours');

        // Test escalation timing
        $events18h = $this->simulateTimePassage(18);
        $this->assertContains('escalate', $events18h, 'Should escalate at 18 hours');

        // Test expiration timing
        $events24h = $this->simulateTimePassage(24);
        $this->assertContains('expire', $events24h, 'Should expire at 24 hours');
    }

    /**
     * Test edge case: reminder at same time as escalation.
     */
    public function testReminderEscalationTiming(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 24,  // Same as SLA
            'sla.hours' => 24        
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // At 24 hours, both reminder and escalation could trigger
        $events = $this->simulateTimePassage(24);
        
        // Escalation should take priority (deterministic selection in processor)
        $this->assertContains('escalate', $events, 'Should escalate at SLA time');
        
        // In this case, we expect escalation to take precedence over reminder
        // since the request is breaching SLA
    }

    /**
     * Test timer system behavior with zero intervals (edge case).
     */
    public function testZeroIntervalTimers(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 0,   // No reminders
            'sla.hours' => 0,        // Immediate escalation
            'expiry.days' => 0       // Immediate expiration
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // With zero intervals, should handle gracefully
        $events = $this->simulateTimePassage(0);
        
        // Should handle edge case without errors
        $this->assertProcessInState('id:pending-approval', 'Should handle zero intervals gracefully');
    }

    /**
     * Test that timer events only trigger in appropriate states.
     */
    public function testTimerEventStateRestriction(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Should be in draft - timer events should not affect this state
        $this->assertProcessInState('id:draft');

        // Try to trigger timer events in wrong state
        $this->triggerReminderTick();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:draft', 'Timer events should not affect draft state');

        $this->triggerEscalateEvent(); 
        $this->doProcessorProcess();
        $this->assertProcessInState('id:draft', 'Escalate event should not affect draft state');

        $this->triggerExpireEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:draft', 'Expire event should not affect draft state');
    }

    /**
     * Test timer behavior in terminal states.
     */
    public function testTimersInTerminalStates(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to approved state (terminal)
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved');

        // Timer events should not affect terminal states
        $this->triggerReminderTick();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Reminders should not affect approved state');

        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Escalation should not affect approved state');

        $this->triggerExpireEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Expiration should not affect approved state');
    }
}
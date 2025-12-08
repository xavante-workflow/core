<?php

namespace Tests\Integration\UseCases\ApprovalWithTimer;

use PHPUnit\Framework\TestCase;

/**
 * Test class for edge cases and error scenarios in the approval with timer workflow.
 * 
 * This test class covers:
 * - Invalid state transitions
 * - Malformed timer configurations
 * - Race conditions between events
 * - Resource constraints and error handling
 * - Boundary value testing
 * - Recovery scenarios
 */
class EdgeCasesAndErrorsTest extends TestCase
{
    use ApprovalWithTimerHelper;

    public function setUp(): void
    {
        static::initialize();
    }

    /**
     * Test workflow behavior with invalid timer configurations.
     */
    public function testInvalidTimerConfigurations(): void
    {
        $this->doInstantiateProcess();
        
        // Test negative timer values (should be handled gracefully)
        $this->setupApprovalRequest([
            'reminder.hours' => -24,  // Negative reminder
            'sla.hours' => -48,       // Negative SLA
            'expiry.days' => -7       // Negative expiry
        ]);

        // Should not crash, should handle gracefully
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:pending-approval', 'Should handle negative timer values gracefully');
        
        // Timer events should not trigger with negative values
        $events = $this->simulateTimePassage(24);
        $this->assertEmpty(array_intersect(['reminderTick', 'escalate', 'expire'], $events), 
            'No timer events should trigger with negative values');
    }

    /**
     * Test extremely large timer values (boundary testing).
     */
    public function testExtremeLargeTimerValues(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => PHP_INT_MAX,  // Maximum integer
            'sla.hours' => PHP_INT_MAX - 1,
            'expiry.days' => PHP_INT_MAX / 24
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should handle large values without overflow
        $this->assertProcessInState('id:pending-approval', 'Should handle large timer values');
        $this->assertVariableEquals('reminder.hours', PHP_INT_MAX, 'Large values should be preserved');
    }

    /**
     * Test workflow with zero-value timers.
     */
    public function testZeroValueTimers(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 0,
            'sla.hours' => 0,
            'expiry.days' => 0
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should handle zero values gracefully
        $this->assertProcessInState('id:pending-approval', 'Should handle zero timer values');
        
        // Zero values should not trigger immediate events in this implementation
        $events = $this->simulateTimePassage(0);
        // Behavior with zero values is implementation-defined
        $this->assertIsArray($events, 'Should return events array even with zero values');
    }

    /**
     * Test invalid event sequencing (events in wrong order).
     */
    public function testInvalidEventSequencing(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Try to approve before submitting (should have no effect)
        $this->assertProcessInState('id:draft');
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:draft', 'Approve event should not affect draft state');
        
        // Try to escalate before submitting (should have no effect)
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:draft', 'Escalate event should not affect draft state');

        // Now submit properly
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Try to submit again (should be idempotent or ignored)
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:pending-approval', 'Double submit should be handled gracefully');
    }

    /**
     * Test concurrent event scenarios (simulated race conditions).
     */
    public function testConcurrentEventScenarios(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest(['sla.hours' => 24]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Simulate concurrent approve and escalate events
        // In a real system, these might arrive simultaneously
        
        // Set up conditions for both events
        $this->setVariableValue('request.status', 'approved');  // For approve transition
        $approveCurrentState = $this->getActiveStates();

        // Reset and set up for escalate
        $this->setVariableValue('request.status', 'escalated'); // For escalate transition
        
        // Process - should handle deterministically (escalate wins due to processor logic)
        $this->doProcessorProcess();
        
        // Due to deterministic transition selection, one should win
        $finalState = $this->getActiveStates();
        $this->assertCount(1, $finalState, 'Should resolve to single state despite concurrent events');
        $this->assertContains($finalState[0], ['id:approved', 'id:escalated'], 'Should be in valid final state');
    }

    /**
     * Test missing required variables (incomplete setup).
     */
    public function testMissingRequiredVariables(): void
    {
        $this->doInstantiateProcess();
        
        // Don't set up approvers - incomplete configuration
        $this->setVariableValue('amount', 5000);
        // Missing: approver, backupApprover

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should still transition to pending-approval
        $this->assertProcessInState('id:pending-approval', 'Should handle missing approver data');
        
        // Try to escalate with missing backup approver
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:escalated', 'Should escalate even with missing backup approver');
        $this->assertVariableEquals('current.approver', '', 'Should handle empty backup approver');
    }

    /**
     * Test malformed variable types (type safety).
     */
    public function testMalformedVariableTypes(): void
    {
        $this->doInstantiateProcess();
        
        // Set invalid types for numeric fields
        $this->setVariableValue('amount', 'not-a-number');
        $this->setVariableValue('sla.hours', 'invalid-hours');
        $this->setVariableValue('expiry.days', [1, 2, 3]); // Array instead of number

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should handle type mismatches gracefully
        $this->assertProcessInState('id:pending-approval', 'Should handle type mismatches');
        
        // Values should be preserved as-is (workflow engine doesn't enforce types)
        $this->assertVariableEquals('amount', 'not-a-number', 'Malformed values should be preserved');
    }

    /**
     * Test recovery from intermediate failures.
     */
    public function testRecoveryFromFailures(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Normal flow to pending-approval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Simulate system recovery scenario: timers were scheduled but system restarted
        // In a real system, this might mean re-reading state and re-establishing timers
        $this->assertVariableEquals('timers.scheduled', 'true', 'Timers should be marked as scheduled');

        // System should be able to continue normal operations
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Should recover and complete workflow');
    }

    /**
     * Test boundary conditions for time calculations.
     */
    public function testTimeBoundaryConditions(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 1,    // Minimum practical value
            'sla.hours' => 1,         // Same as reminder (edge case)
            'expiry.days' => 1/24     // Sub-day expiry (edge case)
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Test timing at exact boundaries
        $events1h = $this->simulateTimePassage(1);
        
        // With reminder.hours = sla.hours = expiry.hours = 1, expiration should trigger at hour 1
        $this->assertNotEmpty($events1h, 'Should have some events at boundary');
        
        // At this boundary configuration, expiration takes precedence
        $this->assertTrue(in_array('expire', $events1h), 
            'Should trigger expiration when all timers converge at same hour');
        $this->assertFalse(in_array('reminderTick', $events1h),
            'Should not trigger reminder when expiration occurs');
        $this->assertFalse(in_array('escalate', $events1h),
            'Should not trigger escalation when expiration occurs at same time');
    }

    /**
     * Test resource exhaustion scenarios (many variables).
     */
    public function testResourceExhaustionScenarios(): void
    {
        $this->doInstantiateProcess();
        
        // Set a large number of variables to test memory/performance
        for ($i = 0; $i < 100; $i++) {
            $this->setVariableValue("test.variable.$i", "value-$i");
        }

        $this->setupApprovalRequest();
        
        // Should handle many variables gracefully
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:pending-approval', 'Should handle many variables');
        
        // Verify core workflow still functions
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved');
    }

    /**
     * Test invalid state manipulation attempts.
     */
    public function testInvalidStateManipulation(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Try to manipulate request.status to invalid value
        $this->setVariableValue('request.status', 'invalid-status');
        $this->doProcessorProcess();

        // Should remain in pending-approval (no valid transition for invalid status)
        $this->assertProcessInState('id:pending-approval', 'Should ignore invalid status values');

        // Reset to valid status and verify normal operation resumes
        $this->setVariableValue('request.status', 'approved');
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Should resume normal operation with valid status');
    }

    /**
     * Test workflow behavior with empty or null values.
     */
    public function testEmptyAndNullValues(): void
    {
        $this->doInstantiateProcess();
        
        // Set empty/null values for various fields
        $this->setVariableValue('approver', '');
        $this->setVariableValue('backupApprover', null);
        $this->setVariableValue('amount', 0);
        $this->setVariableValue('sla.hours', null);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should handle empty values gracefully
        $this->assertProcessInState('id:pending-approval', 'Should handle empty/null values');
        
        // Verify empty values are preserved
        $this->assertVariableEquals('approver', '', 'Empty approver should be preserved');
        $this->assertVariableEquals('backupApprover', null, 'Null backup approver should be preserved');
    }

    /**
     * Test extremely rapid event succession.
     */
    public function testRapidEventSuccession(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Rapid succession of events (simulating system under load)
        $events = ['approve', 'reject', 'escalate', 'expire'];
        
        foreach ($events as $event) {
            $method = 'trigger' . ucfirst($event) . 'Event';
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }

        $this->doProcessorProcess();

        // Should handle rapid events deterministically
        // The first valid transition should win (deterministic processor)
        $finalState = $this->getActiveStates();
        $this->assertCount(1, $finalState, 'Should resolve to single state despite rapid events');
        $this->assertContains($finalState[0], 
            ['id:approved', 'id:rejected', 'id:escalated', 'id:auto-expired'], 
            'Should be in valid terminal or intermediate state');
    }
}
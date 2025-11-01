<?php

namespace Tests\Integration\UseCases\ApprovalWithTimer;

use PHPUnit\Framework\TestCase;

/**
 * Integration test for executing approval workflow with timer-based features.
 * 
 * This test validates the execution of a time-based approval workflow that includes:
 * - Initial state: draft
 * - Timed state: pending-approval (with reminders, escalation, expiration)
 * - Escalated state: escalated (backup approver with expiration)
 * - Terminal states: approved, rejected, auto-expired
 * 
 * Test scenarios covered:
 * 1. Happy Path: Submit → Approve (before any timers)
 * 2. Rejection Path: Submit → Reject (before any timers)
 * 3. Reminder Path: Submit → Wait → Reminders → Approve
 * 4. Escalation Path: Submit → Wait 48h → Escalate → Backup Approve
 * 5. Auto-Expiration Path: Submit → Wait 7d → Auto-Expire
 * 6. Escalation then Expiration: Submit → Escalate → Wait → Expire
 * 
 * The workflow follows these time-based flows:
 * 1. Process starts in 'draft' state
 * 2. 'submit' event → sets request.status = 'submitted' → transition to 'pending-approval'
 * 3. Timer scenarios:
 *    - Every 24h: reminderTick event (while in pending-approval)
 *    - At 48h: escalate event → transition to 'escalated'
 *    - At 7d: expire event → transition to 'auto-expired'
 * 4. User scenarios:
 *    - 'approve' event → sets request.status = 'approved' → transition to 'approved'
 *    - 'reject' event → sets request.status = 'rejected' → transition to 'rejected'
 */
class ExecutionApprovalWithTimerTest extends TestCase
{
    use ApprovalWithTimerHelper;

    /**
     * Set up before each test method to ensure clean state.
     */
    public function setUp(): void
    {
        static::initialize();
    }

    /**
     * Test the happy path: submit request and approve immediately (no timers involved).
     */
    public function testHappyPathImmediateApproval(): void
    {
        // ========================================
        // PHASE 1: PROCESS INSTANTIATION
        // ========================================
        
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'approver' => 'manager@company.com',
            'amount' => 1500
        ]);

        // Verify initial state
        $this->assertProcessInState('id:draft', 'Process should start in draft state');
        $this->assertVariableEquals('request.status', 'draft', 'Initial request status should be draft');

        // ========================================
        // PHASE 2: SUBMIT REQUEST
        // ========================================
        
        $eventId = $this->triggerSubmitEvent();
        $this->assertEquals('id:submit', $eventId);
        
        // Verify submit event action executed
        $this->assertVariableEquals('request.status', 'submitted', 'Submit event should set status to submitted');
        
        // Process workflow - should transition to pending-approval
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval', 'Should transition to pending-approval after submit');

        // Verify entry actions executed
        $this->assertVariableEquals('timers.scheduled', 'true', 'Timers should be scheduled on entry to pending-approval');

        // ========================================
        // PHASE 3: IMMEDIATE APPROVAL (NO TIMERS)
        // ========================================
        
        $this->triggerApproveEvent();
        $this->assertVariableEquals('request.status', 'approved', 'Approve event should set status to approved');
        
        // Process workflow - should transition to approved
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Should transition to approved after approve event');

        // Verify exit actions executed (timers cancelled)
        $this->assertVariableEquals('timers.cancelled', 'true', 'Timers should be cancelled on exit from pending-approval');
        $this->assertVariableEquals('audit.log', 'Exited Pending Approval State', 'Audit log should be recorded');
    }

    /**
     * Test rejection path: submit request and reject immediately.
     */
    public function testRejectionPath(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Submit and verify transition to pending-approval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Reject and verify transition to rejected
        $this->triggerRejectEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:rejected');

        // Verify reject status and cleanup actions
        $this->assertVariableEquals('request.status', 'rejected');
        $this->assertVariableEquals('timers.cancelled', 'true');
    }

    /**
     * Test reminder functionality: reminders are sent every 24 hours while pending.
     */
    public function testReminderFunctionality(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest(['reminder.hours' => 24]);

        // Submit request
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Simulate 24 hours passing - should trigger first reminder
        $events = $this->simulateTimePassage(24);
        $this->assertContains('reminderTick', $events, 'Should trigger reminder at 24 hours');

        $this->triggerReminderTick();
        $this->doProcessorProcess();

        // Should still be in pending-approval but with reminder logged
        $this->assertProcessInState('id:pending-approval', 'Should remain in pending-approval after reminder');
        $this->assertVariableEquals('audit.log', 'Reminder sent', 'Reminder should be logged');

        // Approve after reminder
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved');
    }

    /**
     * Test escalation path: request escalates to backup approver after SLA breach.
     */
    public function testEscalationPath(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'approver' => 'manager@company.com',
            'backupApprover' => 'director@company.com',
            'sla.hours' => 48
        ]);

        // Submit request
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Verify initial approver
        $this->assertVariableEquals('current.approver', '', 'Current approver should be empty initially');

        // Simulate SLA breach (48 hours) - should trigger escalation
        $events = $this->simulateTimePassage(48);
        $this->assertContains('escalate', $events, 'Should trigger escalation at 48 hours');

        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Should transition to escalated state
        $this->assertProcessInState('id:escalated', 'Should transition to escalated after SLA breach');
        $this->assertVariableEquals('request.status', 'escalated', 'Request status should be escalated');

        // Verify reassignment to backup approver (entry action)
        $this->assertVariableEquals('current.approver', 'director@company.com', 'Should reassign to backup approver');

        // Backup approver approves
        $this->triggerApproveEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:approved', 'Backup approver should be able to approve');
    }

    /**
     * Test auto-expiration: request expires after hard deadline.
     */
    public function testAutoExpirationPath(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest(['expiry.days' => 7]);

        // Submit request
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:pending-approval');

        // Simulate hard deadline reached (7 days = 168 hours)
        $events = $this->simulateTimePassage(168);
        $this->assertContains('expire', $events, 'Should trigger expiration at 7 days');

        $this->triggerExpireEvent();
        $this->doProcessorProcess();

        // Should transition to auto-expired
        $this->assertProcessInState('id:auto-expired', 'Should transition to auto-expired after hard deadline');
        $this->assertVariableEquals('request.status', 'expired', 'Request status should be expired');
    }

    /**
     * Test escalation followed by expiration: escalated request expires if not handled.
     */
    public function testEscalationThenExpiration(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'sla.hours' => 48,
            'expiry.days' => 7
        ]);

        // Submit and let it escalate
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Trigger escalation at 48 hours
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');

        // Simulate additional time until expiration (7 days total = 168 hours)
        $this->triggerExpireEvent();
        $this->doProcessorProcess();

        // Should expire from escalated state
        $this->assertProcessInState('id:auto-expired', 'Escalated request should also be able to expire');
    }

    /**
     * Test complete timer simulation scenario with multiple events over time.
     */
    public function testCompleteTimerSimulation(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 24,
            'sla.hours' => 48,
            'expiry.days' => 3 // Shorter for testing
        ]);

        // Submit request
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Execute complete scenario: no user action, let timers run their course
        $log = $this->executeTimerScenario(72, []); // 3 days

        // Verify the timer progression
        $this->assertEquals('id:pending-approval', $log[0]['final_state'], 'Should start in pending-approval');
        
        // Should have reminders at 24h
        if (isset($log[24])) {
            $this->assertContains('reminderTick', $log[24]['timer_events'] ?? [], 'Should send reminder at 24h');
        }
        
        // Should escalate at 48h
        if (isset($log[48])) {
            $this->assertContains('escalate', $log[48]['timer_events'] ?? [], 'Should escalate at 48h');
        }

        // Should expire at 72h (3 days)
        if (isset($log[72])) {
            $this->assertContains('expire', $log[72]['timer_events'] ?? [], 'Should expire at 72h');
            $this->assertEquals('id:auto-expired', $log[72]['final_state'], 'Should end in auto-expired');
        }
    }

    /**
     * Test user intervention during timer progression.
     */
    public function testUserInterventionDuringTimers(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 24,
            'sla.hours' => 48
        ]);

        // Submit request
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Execute scenario with user approval at 36 hours (after reminder, before escalation)
        $log = $this->executeTimerScenario(50, [
            36 => 'approve' // User approves at 36 hours
        ]);

        // Verify reminder happened at 24h
        $this->assertContains('reminderTick', $log[24]['timer_events'] ?? [], 'Reminder should fire at 24h');
        
        // Verify user approval at 36h
        $this->assertEquals('approve', $log[36]['user_action'] ?? '', 'User should approve at 36h');
        $this->assertEquals('id:approved', $log[36]['final_state'], 'Should be approved after user action');
        
        // Verify no escalation happens (user intervened in time)
        $this->assertTrue($log[36]['terminal'] ?? false, 'Should reach terminal state and stop simulation');
    }

    /**
     * Test variable values and audit trail throughout the workflow.
     */
    public function testVariablesAndAuditTrail(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'amount' => 25000,
            'approver' => 'senior.manager@company.com'
        ]);

        // Verify initial variable setup
        $this->assertVariableEquals('amount', 25000, 'Amount should be set correctly');
        $this->assertVariableEquals('approver', 'senior.manager@company.com', 'Primary approver should be set');

        // Submit and verify state changes
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Check audit trail and timer setup
        $this->assertVariableEquals('timers.scheduled', 'true', 'Entry actions should execute');

        // Approve and check final audit
        $this->triggerApproveEvent();
        $this->doProcessorProcess();

        $this->assertVariableEquals('timers.cancelled', 'true', 'Exit actions should execute');
        $this->assertVariableEquals('audit.log', 'Exited Pending Approval State', 'Audit should be recorded');
    }
}
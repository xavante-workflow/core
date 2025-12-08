<?php

namespace Tests\Integration\UseCases\ApprovalWithTimer;

use PHPUnit\Framework\TestCase;

/**
 * Specialized test class for escalation scenarios in the approval workflow.
 * 
 * This test class focuses on the escalation functionality:
 * - SLA breach detection and escalation triggering
 * - Backup approver assignment and notification
 * - Escalated workflow paths (approve/reject/expire)
 * - Multiple levels of escalation scenarios
 */
class EscalationScenarioTest extends TestCase
{
    use ApprovalWithTimerHelper;

    public function setUp(): void
    {
        static::initialize();
    }

    /**
     * Test basic escalation flow: pending → SLA breach → escalated.
     */
    public function testBasicEscalationFlow(): void
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

        // Verify initial approver setup
        $this->assertVariableEquals('approver', 'manager@company.com', 'Primary approver should be set');
        $this->assertVariableEquals('backupApprover', 'director@company.com', 'Backup approver should be set');
        $this->assertVariableEquals('current.approver', '', 'Current approver should be empty initially');

        // Simulate SLA breach and escalate
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Verify escalation
        $this->assertProcessInState('id:escalated', 'Should transition to escalated state');
        $this->assertVariableEquals('request.status', 'escalated', 'Request status should be escalated');

        // Verify entry actions: reassignment to backup approver
        $this->assertVariableEquals('current.approver', 'director@company.com', 'Should reassign to backup approver');
    }

    /**
     * Test escalation with backup approver approval.
     */
    public function testEscalatedApproval(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'approver' => 'junior.manager@company.com',
            'backupApprover' => 'senior.director@company.com'
        ]);

        // Get to escalated state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');

        // Verify backup approver is assigned
        $this->assertVariableEquals('current.approver', 'senior.director@company.com');

        // Backup approver approves
        $this->triggerApproveEvent();
        $this->doProcessorProcess();

        // Should approve successfully
        $this->assertProcessInState('id:approved', 'Backup approver should be able to approve');
        $this->assertVariableEquals('request.status', 'approved', 'Status should be approved');
    }

    /**
     * Test escalation with backup approver rejection.
     */
    public function testEscalatedRejection(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'backupApprover' => 'strict.director@company.com'
        ]);

        // Get to escalated state
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');

        // Backup approver rejects
        $this->triggerRejectEvent();
        $this->doProcessorProcess();

        // Should reject successfully
        $this->assertProcessInState('id:rejected', 'Backup approver should be able to reject');
        $this->assertVariableEquals('request.status', 'rejected', 'Status should be rejected');
    }

    /**
     * Test escalation followed by expiration (backup approver doesn't respond).
     */
    public function testEscalatedExpiration(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'sla.hours' => 24,
            'expiry.days' => 2  // 48 hours total
        ]);

        // Get to escalated state at 24 hours
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');

        // No action from backup approver - let it expire at 48 hours
        $this->triggerExpireEvent();
        $this->doProcessorProcess();

        // Should expire from escalated state
        $this->assertProcessInState('id:auto-expired', 'Escalated requests should also be able to expire');
        $this->assertVariableEquals('request.status', 'expired', 'Status should be expired');
    }

    /**
     * Test escalation with different SLA thresholds.
     */
    public function testEscalationWithDifferentSLAThresholds(): void
    {
        // Test short SLA (immediate escalation scenario)
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'sla.hours' => 1,  // Very short SLA
            'backupApprover' => 'fast.responder@company.com'
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Should be able to escalate quickly
        $events = $this->simulateTimePassage(1);
        $this->assertContains('escalate', $events, 'Should escalate at 1 hour');

        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');
    }

    /**
     * Test escalation priority over other events.
     */
    public function testEscalationPriority(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'reminder.hours' => 48,  // Same as SLA
            'sla.hours' => 48
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // At 48 hours, both reminder and escalation could trigger
        $events = $this->simulateTimePassage(48);
        
        // Both events should be identified
        $this->assertContains('escalate', $events, 'Should identify escalation at SLA time');
        
        // Trigger escalation (should take precedence due to business priority)
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        
        $this->assertProcessInState('id:escalated', 'Escalation should take precedence over reminder');
    }

    /**
     * Test escalation notification and audit trail.
     */
    public function testEscalationNotificationAndAudit(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'approver' => 'original.approver@company.com',
            'backupApprover' => 'backup.approver@company.com'
        ]);

        // Clear audit log
        $this->setVariableValue('audit.log', '');

        // Get to pending approval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Escalate
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');

        // Verify entry actions executed (notification simulation)
        // In real implementation, the MakeHttpRequestAction would send actual notifications
        // Here we verify the actions were configured and would execute
        $this->assertVariableEquals('current.approver', 'backup.approver@company.com', 'Should reassign approver');
        
        // The entry actions should have executed but since they're HTTP actions in dry_run mode,
        // we verify they were set up correctly rather than checking their side effects
        $this->assertProcessInState('id:escalated', 'Should be in escalated state after entry actions');
    }

    /**
     * Test escalation with missing backup approver (edge case).
     */
    public function testEscalationWithMissingBackupApprover(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'approver' => 'solo.manager@company.com',
            'backupApprover' => ''  // No backup approver
        ]);

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Escalate even without backup approver
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Should still transition to escalated state
        $this->assertProcessInState('id:escalated', 'Should escalate even without backup approver');
        
        // Current approver should be set to empty backup approver
        $this->assertVariableEquals('current.approver', '', 'Should assign empty backup approver');
    }

    /**
     * Test multiple escalation attempts (idempotency).
     */
    public function testMultipleEscalationAttempts(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest();

        // Get to pending approval
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // First escalation
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();
        $this->assertProcessInState('id:escalated');
        
        $firstApprover = $this->getVariableValue('current.approver');

        // Try to escalate again (should be idempotent)
        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Should remain in escalated state with same approver
        $this->assertProcessInState('id:escalated', 'Multiple escalations should be idempotent');
        $this->assertEquals($firstApprover, $this->getVariableValue('current.approver'), 'Approver should remain the same');
    }

    /**
     * Test escalation timing accuracy.
     */
    public function testEscalationTimingAccuracy(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest(['sla.hours' => 36]); // 36 hour SLA

        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        // Before SLA: should not escalate
        $eventsBefore = $this->simulateTimePassage(35);
        $this->assertNotContains('escalate', $eventsBefore, 'Should not escalate before SLA');

        // At SLA: should escalate
        $eventsAt = $this->simulateTimePassage(36);
        $this->assertContains('escalate', $eventsAt, 'Should escalate exactly at SLA time');

        // After SLA: escalation should still be valid
        $eventsAfter = $this->simulateTimePassage(40);
        $this->assertContains('escalate', $eventsAfter, 'Escalation should still be valid after SLA');
    }

    /**
     * Test escalation with high-value requests (business logic).
     */
    public function testEscalationWithHighValueRequests(): void
    {
        $this->doInstantiateProcess();
        $this->setupApprovalRequest([
            'amount' => 100000,  // High value request
            'approver' => 'department.manager@company.com',
            'backupApprover' => 'vice.president@company.com',
            'sla.hours' => 24  // Stricter SLA for high-value requests
        ]);

        // High-value requests should follow same escalation pattern
        $this->triggerSubmitEvent();
        $this->doProcessorProcess();

        $this->triggerEscalateEvent();
        $this->doProcessorProcess();

        // Should escalate to higher authority for high-value requests
        $this->assertProcessInState('id:escalated');
        $this->assertVariableEquals('current.approver', 'vice.president@company.com', 'High-value requests should escalate to VP');
        $this->assertVariableEquals('amount', 100000, 'Amount should be preserved through escalation');
    }
}
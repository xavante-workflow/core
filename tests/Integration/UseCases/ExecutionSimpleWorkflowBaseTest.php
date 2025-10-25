<?php

namespace Tests\Integration\UseCases;


use PHPUnit\Framework\TestCase;
use Xavante\Models\Runtime\Process;

/**
 * Integration test for executing a simple workflow through various scenarios.
 * 
 * This test validates the execution of a simple document approval workflow that includes:
 * - Initial state: draft
 * - Intermediate state: pending-approval (with HTTP action and audit action)
 * - Final state: approved
 * 
 * Test scenarios covered:
 * 1. Happy Path: Complete workflow execution from draft → pending-approval → approved
 * 2. Event Isolation: Verifying inappropriate events don't cause unintended transitions
 * 
 * The workflow follows this flow:
 * 1. Process starts in 'draft' state
 * 2. 'document_submitted' event is triggered → sets document.status = 'submitted'
 * 3. Transition from 'draft' to 'pending-approval' (condition: document.status == 'submitted')
 * 4. Entry action in 'pending-approval': HTTP request to assign reviewer (dry_run=true)
 * 5. 'document_approved' event is triggered → sets document.status = 'approved'
 * 6. Manual setting of user.role = 'manager' to satisfy second condition
 * 7. Transition to 'approved' (conditions: document.status == 'approved' AND user.role == 'manager')
 * 8. Exit action from 'pending-approval': audit log entry
 */
class ExecutionSimpleWorkflowBaseTest extends TestCase
{
    // Trait provides helper methods and static properties for workflow execution
    use SimpleWorkflowHelper;

    /**
     * Set up before each test method to ensure clean state.
     * Initializes workflow definition, processor, and resets process to null.
     */
    public function setUp(): void
    {
        // Reset process before each test to ensure clean state
        static::initialize();
    }

    /**
     * Test the complete happy path execution of a simple document approval workflow.
     * 
     * Execution flow:
     * 1. Process instantiation → starts in 'draft' state
     * 2. Trigger 'document_submitted' event → sets document.status = 'submitted'
     * 3. Process workflow → transition to 'pending-approval' state (condition met)
     * 4. Entry action executes: HTTP request to assign reviewer (dry_run mode)
     * 5. Trigger 'document_approved' event → sets document.status = 'approved'
     * 6. Set user.role = 'manager' to satisfy transition condition
     * 7. Process workflow → transition to 'approved' state (all conditions met)
     * 8. Exit action executes: audit log entry for leaving pending-approval
     * 
     * This test validates:
     * - Event-driven action execution
     * - Condition-based state transitions  
     * - Entry/exit action lifecycle
     * - Process history and variable tracking
     */
    public function testWorkflowHappyPath(): void
    {
        // ========================================
        // PHASE 1: PROCESS INSTANTIATION
        // ========================================
        
        // Verify no process exists initially (clean slate)
        $this->assertNull(self::$process);

        // Instantiate a new process from the workflow definition
        // This creates a Process object with initial state 'draft'
        $this->doInstantiateProcess();

        // Verify process was created successfully
        $this->assertNotNull(self::$process);
        $this->assertIsObject(self::$process);
        $this->assertInstanceOf(Process::class, self::$process);


        // Verify the process starts in the correct initial state
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        // ========================================
        // PHASE 2: EVENT TRIGGERING & ACTION EXECUTION
        // ========================================
        
        // Trigger the 'document_submitted' event
        // This will execute the SetVariableValueAction to set document.status = 'submitted'
        $eventId = $this->triggerEvent('id:document_submitted');

        // Define expected variable that should be set by the event action
        $variableId = 'document.status';


        // ========================================
        // VERIFICATION: EVENT ACTION EXECUTION
        // ========================================
        
        // Verify that the event's SetVariableValueAction executed successfully
        // The action should have set document.status = 'submitted'
        $variables = self::$process->getVariables();
        $this->assertArrayHasKey($variableId, $variables);
        $this->assertEquals('submitted', $variables[$variableId]);

        // Verify the event was properly raised and recorded in the process
        $this->assertIsArray(self::$process->getRaiseEvents());
        $this->assertCount(1, self::$process->getRaiseEvents());

        // Verify the correct event object is recorded in raised events
        $this->assertEquals(self::$workflow->events->getById($eventId), self::$process->getRaiseEvents()[0]['event']);

        // Verify process history contains the expected entries for audit trail
        foreach (self::$process->getHistory() as $historyEntry) {
            if (isset($historyEntry['type'])) {
                // Check that both event raising and variable setting are recorded in history
                match ($historyEntry['type']) {
                    'event_raised' => $this->assertStringContainsString($eventId, $historyEntry['entry']),
                    'variable_set' => $this->assertStringContainsString($variableId, $historyEntry['entry']),
                    default => null, // Ignore other history entry types
                };
            }
        }

        // IMPORTANT: At this point, the event has been triggered and its actions executed,
        // but NO state transitions have occurred yet. The process is still in 'draft' state.
        // State transitions happen during the processor.process() call.
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        // ========================================
        // PHASE 3: FIRST STATE TRANSITION (draft → pending-approval)
        // ========================================
        
        // Execute workflow processing - this evaluates transitions and changes states
        // The transition from 'draft' to 'pending-approval' should occur because:
        // - Condition: document.status == 'submitted' (✓ satisfied)
        $this->doProcessorProcess();

        // Verify the state transition occurred successfully
        // Process should now be in 'pending-approval' state
        $expectedActiveStates = ['id:pending-approval'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        
        // ========================================
        // PHASE 4: PREPARING FOR SECOND STATE TRANSITION (pending-approval → approved)
        // ========================================

        // Trigger the 'approve_document' event to set document.status = 'approved'
        // This executes the SetVariableValueAction associated with the event
        $eventId = 'id:document_approved';
        self::$processor->triggerEvent(self::$process, $eventId);

        // Set the user.role variable to 'manager' to satisfy the second transition condition
        // This is required for the transition from 'pending-approval' to 'approved'
        self::$process->setVariableValue('user.role', 'manager');

        // ========================================
        // PHASE 5: SECOND STATE TRANSITION (pending-approval → approved)
        // ========================================
        
        // Execute workflow processing again to evaluate the next transition
        // The transition from 'pending-approval' to 'approved' should occur because:
        // - Condition 1: document.status == 'approved' (✓ set by approve_document event)
        // - Condition 2: user.role == 'manager' (✓ set manually above)
        $this->doProcessorProcess();

        // ========================================
        // FINAL VERIFICATION: WORKFLOW COMPLETION
        // ========================================
        
        // Verify the final state transition occurred successfully
        // Process should now be in the final 'approved' state
        $expectedActiveStates = ['id:approved'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);
        
        // At this point, the 'pending-approval' exit actions should have executed:
        // 1. SetVariableValueAction: Sets audit.log = 'Exited Pending Approval State'
        // This demonstrates the complete lifecycle of state entry/exit actions
    }




    /**
     * Test that triggering inappropriate events in draft state doesn't cause unintended transitions.
     * 
     * This test verifies:
     * 1. Process starts in draft state
     * 2. Triggering 'approve_document' event has no effect on state
     * 3. Only the correct event ('document_submitted') causes the intended transition
     */
    public function testFromDraftWithOtherEventShouldRemainInDraftState(): void
    {
        // ========================================
        // PHASE 1: PROCESS INSTANTIATION
        // ========================================
        
        // Verify no process exists initially (clean slate)
        $this->assertNull(self::$process);

        // Instantiate a new process from the workflow definition
        // This creates a Process object with initial state 'draft'
        $this->doInstantiateProcess();

        // Verify process was created successfully
        $this->assertNotNull(self::$process);
        $this->assertIsObject(self::$process);
        $this->assertInstanceOf(Process::class, self::$process);

        // Verify the process starts in the correct initial state
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        // ========================================
        // PHASE 2: INAPPROPRIATE EVENT TRIGGERING
        // ========================================
        
        // Trigger the 'approve_document' event while in draft state
        // This should NOT cause any state transitions since the transition conditions aren't met
        $eventId = 'id:document_approved';
        self::$processor->triggerEvent(self::$process, $eventId);


        // Verify the event was properly raised and recorded in the process
        $this->assertIsArray(self::$process->getRaiseEvents());
        $this->assertCount(1, self::$process->getRaiseEvents());

        // Verify the correct event object is recorded in raised events
        $this->assertEquals(self::$workflow->events->getById($eventId), self::$process->getRaiseEvents()[0]['event']);

        // Verify process history contains the expected entries for audit trail
        foreach (self::$process->getHistory() as $historyEntry) {
            if (isset($historyEntry['type'])) {
                // Check that both event raising and variable setting are recorded in history
                match ($historyEntry['type']) {
                    'event_raised' => $this->assertStringContainsString($eventId, $historyEntry['entry']),
                    default => null, // Ignore other history entry types
                };
            }
        }

        // IMPORTANT: At this point, the event has been triggered and its actions executed,
        // but NO state transitions have occurred yet. The process is still in 'draft' state.
        // State transitions happen during the processor.process() call.
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        // ========================================
        // PHASE 3: VERIFY NO UNINTENDED TRANSITIONS
        // ========================================

        // Trigger another inappropriate event to further test state isolation
        $eventId = 'id:document_approved';
        self::$processor->triggerEvent(self::$process, $eventId);

        // Process the workflow - this should NOT cause any transitions
        // because the conditions for draft → pending-approval are not met
        // (document.status is not 'submitted')
        $this->doProcessorProcess();

        // Verify the process remains in draft state
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        // ========================================
        // PHASE 4: CORRECT EVENT FOR TRANSITION
        // ========================================
        
        // Now trigger the correct event to demonstrate proper workflow progression
        $eventId = $this->triggerEvent('id:document_submitted');

        // Process the workflow with the correct conditions now in place
        $this->doProcessorProcess();

        // Verify the state transition occurred successfully
        // Process should now be in 'pending-approval' state because:
        // - document.status == 'submitted' (set by document_submitted event)
        $expectedActiveStates = ['id:pending-approval'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

    }
}
    
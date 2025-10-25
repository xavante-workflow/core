<?php

namespace Tests\Integration\UseCases;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Runtime\Process;
use Xavante\Runtime\Processor;

/**
 * Trait providing helper methods and shared state for simple workflow testing.
 * 
 * This trait encapsulates the common workflow execution patterns used across
 * multiple test scenarios for the simple document approval workflow.
 */
trait SimpleWorkflowHelper {

    /** @var Workflow The workflow definition loaded from 1-simple-workflow.php */
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
        // Load the simple approval workflow definition
        self::$workflow = require __DIR__ . '/1-simple-workflow.php';
        
        // Create processor with empty context (no external dependencies)
        $context = [];
        self::$processor = new Processor($context);

        self::$process = null;
    }

    /**
     * Create a new process instance from the workflow definition.
     * The process starts in the initial state defined by the workflow.
     */
    protected function doInstantiateProcess(): void
    {
        self::$process = self::$processor->instantiate(self::$workflow);
    }


    /**
     * Trigger any event on the current process.
     * 
     * This method can trigger any workflow event:
     * - 'id:document_submitted' → executes SetVariableValueAction: document.status = 'submitted'
     * - 'id:document_approved' → executes SetVariableValueAction: document.status = 'approved'
     * 
     * @param string $eventId The ID of the event to trigger (defaults to 'document_submitted')
     * @return string The event ID that was triggered
     */
    protected function triggerEvent($eventId = 'id:document_submitted'): string
    {
        // Trigger the event - this executes all actions associated with the event
        self::$processor->triggerEvent(self::$process, $eventId);

        return $eventId;
    }

    /**
     * Execute workflow processing cycle.
     * 
     * This evaluates all possible transitions from current active states:
     * - Checks transition conditions
     * - Executes state transitions if conditions are met
     * - Runs entry/exit actions for states
     * - Updates process state and history
     */
    protected function doProcessorProcess(): void {
        self::$processor->process(self::$process);
    }

}
<?php

namespace Tests\Unit\Runtime;

use PHPUnit\Framework\TestCase;
use Xavante\Conditions\Operators\OperatorConstants;
use Xavante\Models\Domain\Condition;
use Xavante\Models\Domain\State;
use Xavante\Models\Domain\Transition;
use Xavante\Models\Domain\Variable;
use Xavante\Models\Domain\Workflow;
use Xavante\Runtime\Processor;

class ProcessorTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new Processor();
    }

    /**
     * Test basic instantiation of a workflow process
     */
    public function testInstantiateProcess(): void
    {
        $workflow = $this->createSimpleWorkflow();
        $process = $this->processor->instantiate($workflow);

        $this->assertNotNull($process);
        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->assertSame($workflow, $process->getWorkflow());
    }

    /**
     * Test transition without conditions - should always execute
     */
    public function testTransitionWithoutConditions(): void
    {
        // Create workflow with unconditional transition
        $workflow = new Workflow([
            'id' => 'test-unconditional',
            'name' => 'Test Unconditional Workflow',
            'description' => 'Tests unconditional transitions'
        ]);

        // Add states
        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('completed', 'Completed State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add transition without conditions
        $transition = new Transition('complete', 'Complete Transition', 'start', 'completed');
        $workflow->addTransition($transition);

        // Create process and execute
        $process = $this->processor->instantiate($workflow);
        $this->assertEquals(['start'], $process->getActiveStatesIds());

        $this->processor->process($process);

        // Should transition to completed state
        $this->assertEquals(['completed'], $process->getActiveStatesIds());
    }

    /**
     * Test transition with single condition that passes
     */
    public function testTransitionWithSingleConditionPassing(): void
    {
        $workflow = $this->createWorkflowWithConditions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variable to pass condition (score >= 80)
        $process->setVariableValue('score', 85);

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should transition to approved state
        $this->assertEquals(['approved'], $process->getActiveStatesIds());
    }

    /**
     * Test transition with single condition that fails
     */
    public function testTransitionWithSingleConditionFailing(): void
    {
        $workflow = $this->createWorkflowWithConditions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variable to fail condition (score < 80)
        $process->setVariableValue('score', 60);

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should transition to rejected state
        $this->assertEquals(['rejected'], $process->getActiveStatesIds());
    }

    /**
     * Test transition with multiple conditions (AND logic) - all pass
     */
    public function testTransitionWithMultipleConditionsAllPass(): void
    {
        $workflow = $this->createWorkflowWithMultipleConditions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variables to pass all conditions
        $process->setVariableValue('score', 90);      // >= 80 ✓
        $process->setVariableValue('status', 'active'); // = 'active' ✓
        $process->setVariableValue('priority', 'high');  // = 'high' ✓

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should transition to approved state
        $this->assertEquals(['approved'], $process->getActiveStatesIds());
    }

    /**
     * Test transition with multiple conditions (AND logic) - one fails
     */
    public function testTransitionWithMultipleConditionsOneFails(): void
    {
        $workflow = $this->createWorkflowWithMultipleConditions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variables where one condition fails
        $process->setVariableValue('score', 90);         // >= 80 ✓
        $process->setVariableValue('status', 'inactive'); // = 'active' ✗
        $process->setVariableValue('priority', 'high');   // = 'high' ✓

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should remain in start state (no transition taken)
        $this->assertEquals(['start'], $process->getActiveStatesIds());
    }

    /**
     * Test multiple competing transitions - deterministic selection (most specific wins)
     * (Processor now selects the transition with most conditions)
     */
    public function testMultipleCompetingTransitions(): void
    {
        $workflow = $this->createWorkflowWithCompetingTransitions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variable to trigger both transitions (score > 90 AND score >= 50)
        $process->setVariableValue('score', 95);

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Only the high-priority transition should execute (both have 1 condition, 
        // but high-priority is added first so takes precedence in case of tie)
        $this->assertEquals(['high-priority'], $process->getActiveStatesIds());
    }

    /**
     * Test single matching transition among competing ones
     */
    public function testSingleMatchingTransitionAmongCompeting(): void
    {
        $workflow = $this->createWorkflowWithCompetingTransitions();
        $process = $this->processor->instantiate($workflow);
        
        // Set variable to trigger only normal transition (50 <= score <= 90)
        $process->setVariableValue('score', 80);

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Only normal transition should execute
        $this->assertEquals(['normal'], $process->getActiveStatesIds());
    }

    /**
     * Test different operator types with conditions
     */
    public function testDifferentOperatorTypes(): void
    {
        $workflow = $this->createWorkflowWithDifferentOperators();
        $process = $this->processor->instantiate($workflow);
        
        // Test string operator (contains)
        $process->setVariableValue('message', 'Hello World');
        $process->setVariableValue('count', 5);

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should transition to processed state
        $this->assertEquals(['processed'], $process->getActiveStatesIds());
    }

    /**
     * Test that no transitions occur when no conditions are met
     */
    public function testNoTransitionsWhenNoConditionsMet(): void
    {
        // Create a workflow where a specific value doesn't meet any condition
        $workflow = new Workflow([
            'id' => 'no-conditions-test',
            'name' => 'No Conditions Test Workflow'
        ]);

        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('processed', 'Processed State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        $workflow->addVariable(new Variable('value', 'Value', 'integer', 0));

        // Add transition that requires exactly 100
        $transition = new Transition('process', 'Process', 'start', 'processed');
        $transition->addCondition(new Condition(
            'value', 
            OperatorConstants::EQUALS,
            100
        ));
        $workflow->addTransition($transition);

        $process = $this->processor->instantiate($workflow);
        
        // Set value that doesn't meet the condition
        $process->setVariableValue('value', 50); // Not equal to 100

        $this->assertEquals(['start'], $process->getActiveStatesIds());
        $this->processor->process($process);

        // Should remain in start state
        $this->assertEquals(['start'], $process->getActiveStatesIds());
    }

    /**
     * Helper method to create a simple workflow
     */
    private function createSimpleWorkflow(): Workflow
    {
        $workflow = new Workflow([
            'id' => 'simple-test',
            'name' => 'Simple Test Workflow'
        ]);

        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('end', 'End State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        return $workflow;
    }

    /**
     * Helper method to create workflow with single conditions
     */
    private function createWorkflowWithConditions(): Workflow
    {
        $workflow = new Workflow([
            'id' => 'conditional-test',
            'name' => 'Conditional Test Workflow'
        ]);

        // Add states
        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('approved', 'Approved State', 'final'));
        $workflow->addState(new State('rejected', 'Rejected State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add variable
        $workflow->addVariable(new Variable('score', 'Score', 'integer', 0));

        // Add approve transition with condition (score >= 80)
        $approveTransition = new Transition('approve', 'Approve', 'start', 'approved');
        $approveTransition->addCondition(new Condition(
            'score', 
            OperatorConstants::GREATER_THAN_OR_EQUAL,
            80
        ));
        $workflow->addTransition($approveTransition);

        // Add reject transition with condition (score < 80)
        $rejectTransition = new Transition('reject', 'Reject', 'start', 'rejected');
        $rejectTransition->addCondition(new Condition(
            'score', 
            OperatorConstants::LESS_THAN,
            80
        ));
        $workflow->addTransition($rejectTransition);

        return $workflow;
    }

    /**
     * Helper method to create workflow with multiple conditions on single transition
     */
    private function createWorkflowWithMultipleConditions(): Workflow
    {
        $workflow = new Workflow([
            'id' => 'multi-condition-test',
            'name' => 'Multi Condition Test Workflow'
        ]);

        // Add states
        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('approved', 'Approved State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add variables
        $workflow->addVariable(new Variable('score', 'Score', 'integer', 0));
        $workflow->addVariable(new Variable('status', 'Status', 'string', 'pending'));
        $workflow->addVariable(new Variable('priority', 'Priority', 'string', 'low'));

        // Add transition with multiple conditions (all must pass)
        $transition = new Transition('approve', 'Approve', 'start', 'approved');
        $transition->addCondition(new Condition(
            'score', 
            OperatorConstants::GREATER_THAN_OR_EQUAL,
            80
        ));
        $transition->addCondition(new Condition(
            'status', 
            OperatorConstants::EQUALS,
            'active'
        ));
        $transition->addCondition(new Condition(
            'priority', 
            OperatorConstants::EQUALS,
            'high'
        ));
        $workflow->addTransition($transition);

        return $workflow;
    }

    /**
     * Helper method to create workflow with competing transitions
     */
    private function createWorkflowWithCompetingTransitions(): Workflow
    {
        $workflow = new Workflow([
            'id' => 'competing-test',
            'name' => 'Competing Transitions Test Workflow'
        ]);

        // Add states
        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('high-priority', 'High Priority State', 'final'));
        $workflow->addState(new State('normal', 'Normal State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add variable
        $workflow->addVariable(new Variable('score', 'Score', 'integer', 0));

        // Add high priority transition (score > 90)
        $highPriorityTransition = new Transition('high', 'High Priority', 'start', 'high-priority');
        $highPriorityTransition->addCondition(new Condition(
            'score', 
            OperatorConstants::GREATER_THAN,
            90
        ));
        $workflow->addTransition($highPriorityTransition);

        // Add normal transition (score >= 50)
        $normalTransition = new Transition('normal', 'Normal', 'start', 'normal');
        $normalTransition->addCondition(new Condition(
            'score', 
            OperatorConstants::GREATER_THAN_OR_EQUAL,
            50
        ));
        $workflow->addTransition($normalTransition);

        return $workflow;
    }

    /**
     * Helper method to create workflow with different operator types
     */
    private function createWorkflowWithDifferentOperators(): Workflow
    {
        $workflow = new Workflow([
            'id' => 'operator-test',
            'name' => 'Different Operators Test Workflow'
        ]);

        // Add states
        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('processed', 'Processed State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add variables
        $workflow->addVariable(new Variable('message', 'Message', 'string', ''));
        $workflow->addVariable(new Variable('count', 'Count', 'integer', 0));

        // Add transition with string and numeric conditions
        $transition = new Transition('process', 'Process', 'start', 'processed');
        $transition->addCondition(new Condition(
            'message', 
            OperatorConstants::CONTAINS,
            'World'
        ));
        $transition->addCondition(new Condition(
            'count', 
            OperatorConstants::GREATER_THAN,
            3
        ));
        $workflow->addTransition($transition);

        return $workflow;
    }

    /**
     * Test deterministic transition selection - more conditions = higher priority
     */
    public function testDeterministicTransitionSelection(): void
    {
        $workflow = new Workflow([
            'id' => 'deterministic-test',
            'name' => 'Deterministic Selection Test'
        ]);

        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('specific', 'Specific Match State', 'final'));
        $workflow->addState(new State('general', 'General Match State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        $workflow->addVariable(new Variable('score', 'Score', 'integer', 0));
        $workflow->addVariable(new Variable('status', 'Status', 'string', 'pending'));

        // Transition with 2 conditions (more specific)
        $specificTransition = new Transition('specific', 'Specific', 'start', 'specific');
        $specificTransition->addCondition(new Condition('score', OperatorConstants::GREATER_THAN, 80));
        $specificTransition->addCondition(new Condition('status', OperatorConstants::EQUALS, 'active'));
        $workflow->addTransition($specificTransition);

        // Transition with 1 condition (less specific)
        $generalTransition = new Transition('general', 'General', 'start', 'general');
        $generalTransition->addCondition(new Condition('score', OperatorConstants::GREATER_THAN, 50));
        $workflow->addTransition($generalTransition);

        $process = $this->processor->instantiate($workflow);
        $process->setVariableValue('score', 85);
        $process->setVariableValue('status', 'active');

        // Both transitions could match, but specific (2 conditions) should win
        $this->processor->process($process);
        $this->assertEquals(['specific'], $process->getActiveStatesIds());
    }

    /**
     * Test error handling for invalid operator in condition
     */
    public function testInvalidOperatorThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported operator 'invalid_operator' in condition");

        $workflow = new Workflow([
            'id' => 'invalid-operator-test',
            'name' => 'Invalid Operator Test'
        ]);

        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('end', 'End State', 'final'));
        $workflow->setInitialStatesIds(['start']);
        $workflow->addVariable(new Variable('test', 'Test', 'string', 'value'));

        // Create transition with invalid operator
        $transition = new Transition('test', 'Test', 'start', 'end');
        $transition->addCondition(new Condition('test', 'invalid_operator', 'value'));
        $workflow->addTransition($transition);

        $process = $this->processor->instantiate($workflow);
        $this->processor->process($process);
    }

    /**
     * Test workflow with no transitions
     */
    public function testWorkflowWithNoTransitions(): void
    {
        $workflow = new Workflow([
            'id' => 'no-transitions-test',
            'name' => 'No Transitions Test'
        ]);

        $workflow->addState(new State('isolated', 'Isolated State', 'initial'));
        $workflow->setInitialStatesIds(['isolated']);

        $process = $this->processor->instantiate($workflow);
        $initialStates = $process->getActiveStatesIds();

        $this->processor->process($process);

        // Should remain in same state when no transitions exist
        $this->assertEquals($initialStates, $process->getActiveStatesIds());
    }

    /**
     * Test transition with complex condition using different data types
     */
    public function testTransitionWithComplexDataTypes(): void
    {
        $workflow = new Workflow([
            'id' => 'complex-types-test',
            'name' => 'Complex Data Types Test'
        ]);

        $workflow->addState(new State('start', 'Start State', 'initial'));
        $workflow->addState(new State('string-match', 'String Match State', 'final'));
        $workflow->addState(new State('number-match', 'Number Match State', 'final'));
        $workflow->setInitialStatesIds(['start']);

        // Add variables with different types
        $workflow->addVariable(new Variable('text', 'Text', 'string', ''));
        $workflow->addVariable(new Variable('number', 'Number', 'float', 0.0));

        // String condition transition
        $stringTransition = new Transition('string-test', 'String Test', 'start', 'string-match');
        $stringTransition->addCondition(new Condition(
            'text', 
            OperatorConstants::STARTS_WITH,
            'Hello'
        ));
        $workflow->addTransition($stringTransition);

        // Numeric condition transition  
        $numberTransition = new Transition('number-test', 'Number Test', 'start', 'number-match');
        $numberTransition->addCondition(new Condition(
            'number', 
            OperatorConstants::GREATER_THAN,
            10.5
        ));
        $workflow->addTransition($numberTransition);

        // Test string condition
        $process1 = $this->processor->instantiate($workflow);
        $process1->setVariableValue('text', 'Hello World');
        $process1->setVariableValue('number', 5.0);

        $this->processor->process($process1);
        $this->assertEquals(['string-match'], $process1->getActiveStatesIds());

        // Test number condition
        $process2 = $this->processor->instantiate($workflow);
        $process2->setVariableValue('text', 'Goodbye');
        $process2->setVariableValue('number', 15.7);

        $this->processor->process($process2);
        $this->assertEquals(['number-match'], $process2->getActiveStatesIds());
    }

    /**
     * Test multiple process instances from same workflow
     */
    public function testMultipleProcessInstances(): void
    {
        $workflow = $this->createWorkflowWithConditions();
        
        // Create multiple process instances
        $process1 = $this->processor->instantiate($workflow);
        $process2 = $this->processor->instantiate($workflow);
        
        // Set different values for each process
        $process1->setVariableValue('score', 90); // Should approve
        $process2->setVariableValue('score', 30); // Should reject

        // Process them independently
        $this->processor->process($process1);
        $this->processor->process($process2);

        // Verify independent state transitions
        $this->assertEquals(['approved'], $process1->getActiveStatesIds());
        $this->assertEquals(['rejected'], $process2->getActiveStatesIds());

        // Verify processes have unique IDs
        $this->assertNotEquals($process1->id, $process2->id);
    }
}
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Xavante\Conditions\Operators\OperatorConstants;
use Xavante\Models\Domain\Condition;
use Xavante\Models\Domain\Event;
use Xavante\Models\Domain\State;
use Xavante\Models\Domain\Transition;
use Xavante\Models\Domain\Variable;
use Xavante\Models\Domain\Workflow;
use Xavante\Runtime\Processor;

echo "=== Processor with Operators Example ===\n\n";

// Create workflow manually (following integration test pattern)
$workflow = new Workflow([
    'id' => 'processor-example',
    'name' => 'Processor Example Workflow',
    'description' => 'Demonstrates operator usage in processor'
]);

// Add states
$workflow->addState(new State('start', 'Start State', 'initial'));
$workflow->addState(new State('approved', 'Approved State', 'final'));
$workflow->addState(new State('rejected', 'Rejected State', 'final'));

// Set initial state
$workflow->setInitialStatesIds(['start']);

// Add variables
$workflow->addVariable(new Variable('score', 'Score', 'integer', 0));
$workflow->addVariable(new Variable('status', 'Status', 'string', 'pending'));

// Add event
$evaluateEvent = new Event('evaluate', 'Evaluate Request');
$workflow->addEvent($evaluateEvent);

// Create transitions with conditions using operator constants
$approveTransition = new Transition('approve', 'Approve Transition', 'start', 'approved');
$approveTransition->addCondition(new Condition(
    'score', 
    OperatorConstants::GREATER_THAN_OR_EQUAL,  // Using constants for type safety
    80
));
$approveTransition->addCondition(new Condition(
    'status', 
    OperatorConstants::EQUALS,  // Using constants
    'active'
));
$workflow->addTransition($approveTransition);

$rejectTransition = new Transition('reject', 'Reject Transition', 'start', 'rejected');
$rejectTransition->addCondition(new Condition(
    'score', 
    OperatorConstants::LESS_THAN,  // Using constants
    80
));
$workflow->addTransition($rejectTransition);

$processor = new Processor();

echo "1. Created workflow with conditions using operator constants:\n";
echo "   - Approval requires: score >= 80 AND status = 'active'\n";
echo "   - Rejection requires: score < 80\n";
echo "   - Processor will evaluate conditions using OperatorRegistry\n\n";

// Test case 1: High score but inactive status
echo "2. Testing Case 1: High score (90) but status 'pending'\n";
$process1 = $processor->instantiate($workflow);
$process1->setVariableValue('score', 90);
$process1->setVariableValue('status', 'pending');

echo "   Initial active states: " . implode(', ', $process1->getActiveStatesIds()) . "\n";
echo "   Variables: score=" . $process1->getVariableValue('score') . ", status=" . $process1->getVariableValue('status') . "\n";
$processor->process($process1);
echo "   Final active states: " . implode(', ', $process1->getActiveStatesIds()) . "\n";
echo "   Result: Should be rejected because status != 'active'\n\n";

// Test case 2: High score and active status
echo "3. Testing Case 2: High score (85) and status 'active'\n"; 
$process2 = $processor->instantiate($workflow);
$process2->setVariableValue('score', 85);
$process2->setVariableValue('status', 'active');

echo "   Initial active states: " . implode(', ', $process2->getActiveStatesIds()) . "\n";
echo "   Variables: score=" . $process2->getVariableValue('score') . ", status=" . $process2->getVariableValue('status') . "\n";
$processor->process($process2);
echo "   Final active states: " . implode(', ', $process2->getActiveStatesIds()) . "\n";
echo "   Result: Should be approved (score >= 80 AND status = 'active')\n\n";

// Test case 3: Low score
echo "4. Testing Case 3: Low score (60) with active status\n";
$process3 = $processor->instantiate($workflow);
$process3->setVariableValue('score', 60);
$process3->setVariableValue('status', 'active');

echo "   Initial active states: " . implode(', ', $process3->getActiveStatesIds()) . "\n";
echo "   Variables: score=" . $process3->getVariableValue('score') . ", status=" . $process3->getVariableValue('status') . "\n";
$processor->process($process3);
echo "   Final active states: " . implode(', ', $process3->getActiveStatesIds()) . "\n";
echo "   Result: Should be rejected (score < 80)\n\n";

echo "=== Processor Operator Integration Complete ===\n";
echo "The processor now uses OperatorRegistry for dynamic operator resolution,\n";
echo "supporting all operator types with type-safe constants.\n";
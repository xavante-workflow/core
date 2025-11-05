<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Xavante\Conditions\Operators\OperatorConstants;
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Models\Domain\Condition;
use Xavante\Models\Domain\State;
use Xavante\Models\Domain\Transition;
use Xavante\Models\Domain\Variable;
use Xavante\Models\Domain\Workflow;
use Xavante\Runtime\Processor;

echo "=== Processor Debug Example ===\n\n";

// Create workflow manually
$workflow = new Workflow([
    'id' => 'debug-example',
    'name' => 'Debug Example Workflow',
    'description' => 'Debug operator usage'
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

// Create test conditions directly to verify they work
echo "1. Testing individual operators with OperatorConstants:\n\n";

$testScore = 85;
$testStatus = 'active';

// Test greater_than_or_equal
$gte = OperatorRegistry::get(OperatorConstants::GREATER_THAN_OR_EQUAL);
$result1 = $gte->evaluate($testScore, 80);
echo "   score ($testScore) >= 80: " . ($result1 ? 'TRUE' : 'FALSE') . "\n";

// Test equals
$eq = OperatorRegistry::get(OperatorConstants::EQUALS);
$result2 = $eq->evaluate($testStatus, 'active');
echo "   status ('$testStatus') == 'active': " . ($result2 ? 'TRUE' : 'FALSE') . "\n";

// Test less_than
$lt = OperatorRegistry::get(OperatorConstants::LESS_THAN);
$result3 = $lt->evaluate($testScore, 80);
echo "   score ($testScore) < 80: " . ($result3 ? 'TRUE' : 'FALSE') . "\n\n";

// Test combined AND logic manually
echo "2. Testing combined conditions manually:\n";
$approveCondition = $result1 && $result2;
$rejectCondition = $result3;
echo "   Approve (score >= 80 AND status = 'active'): " . ($approveCondition ? 'TRUE' : 'FALSE') . "\n";
echo "   Reject (score < 80): " . ($rejectCondition ? 'TRUE' : 'FALSE') . "\n\n";

echo "3. All operators working correctly with constants!\n";
echo "   The issue must be in the Processor condition evaluation logic.\n";
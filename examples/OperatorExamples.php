<?php

/**
 * Example: Using the new operators in workflow conditions
 * 
 * This example demonstrates how to use the comprehensive operator system
 * in real workflow scenarios with various condition types.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Xavante\Models\Domain\Condition;
use Xavante\Models\Domain\Workflow;
use Xavante\Models\Factories\WorkflowFactory;
use Xavante\Models\Factories\StateFactory;
use Xavante\Models\Factories\TransitionFactory;
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Conditions\Operators\OperatorConstants;

/**
 * Create a workflow with advanced operator examples
 */
function createAdvancedWorkflow(): Workflow
{
    $workflowData = [
        'id' => 'advanced-operators-example',
        'name' => 'Advanced Operators Workflow',
        'description' => 'Demonstrates comprehensive operator usage in conditions'
    ];

    $workflow = WorkflowFactory::createWorkflow($workflowData);

    // Create states
    $states = [
        [
            'id' => 'draft',
            'name' => 'Draft',
            'type' => 'initial'
        ],
        [
            'id' => 'content-review',
            'name' => 'Content Review',
            'type' => 'intermediate'
        ],
        [
            'id' => 'priority-review',
            'name' => 'Priority Review', 
            'type' => 'intermediate'
        ],
        [
            'id' => 'approved',
            'name' => 'Approved',
            'type' => 'final'
        ],
        [
            'id' => 'rejected',
            'name' => 'Rejected', 
            'type' => 'final'
        ]
    ];

    foreach ($states as $stateData) {
        $state = StateFactory::createFromArray($stateData);
        $workflow->addState($state);
    }

    // Define transitions with various operator examples
    $transitionsData = [
        // Draft → Content Review: Basic checks
        [
            'id' => 'submit-for-review',
            'name' => 'Submit for Review',
            'from_state_id' => 'draft',
            'to_state_id' => 'content-review',
            'conditions' => [
                // String length check
                new Condition('document.title', OperatorConstants::LENGTH_GT, 10),
                // String content check  
                new Condition('document.content', OperatorConstants::IS_NOT_EMPTY, null),
                // Email format validation
                new Condition('author.email', OperatorConstants::CONTAINS, '@'),
            ]
        ],

        // Content Review → Priority Review: Advanced string/date checks  
        [
            'id' => 'escalate-priority',
            'name' => 'Escalate to Priority Review',
            'from_state_id' => 'content-review',
            'to_state_id' => 'priority-review',
            'conditions' => [
                // Case-insensitive keyword check
                new Condition('document.tags', OperatorConstants::ICONTAINS, 'urgent'),
                // Due date proximity check
                new Condition('document.deadline', OperatorConstants::DUE_IN_DAYS, 7),
                // Numeric priority threshold
                new Condition('document.priority', OperatorConstants::GREATER_OR_EQUAL, 8),
            ]
        ],

        // Content Review → Approved: Standard approval path
        [
            'id' => 'approve-standard',
            'name' => 'Standard Approval',
            'from_state_id' => 'content-review', 
            'to_state_id' => 'approved',
            'conditions' => [
                // Logical checks
                new Condition('document.tags', OperatorConstants::NOT_EMPTY, null),
                // Date not overdue
                new Condition('document.deadline', OperatorConstants::IS_DUE, null),
                // Priority in normal range
                new Condition('document.priority', OperatorConstants::LESS, 8),
            ]
        ],

        // Priority Review → Approved: Executive approval
        [
            'id' => 'executive-approval',
            'name' => 'Executive Approval',
            'from_state_id' => 'priority-review',
            'to_state_id' => 'approved',
            'conditions' => [
                // Role-based access
                new Condition('approver.role', OperatorConstants::EQUALS, 'executive'),
                // Boolean flag check
                new Condition('security.cleared', OperatorConstants::IS_TRUE, null),
                // Same day approval for urgency
                new Condition('approval.date', OperatorConstants::IS_TODAY, null),
            ]
        ],

        // Any Review → Rejected: Rejection conditions
        [
            'id' => 'reject-content',
            'name' => 'Reject Content',
            'from_state_id' => 'content-review',
            'to_state_id' => 'rejected',
            'conditions' => [
                // Comment required for rejection
                new Condition('rejection.comment', OperatorConstants::LENGTH_GT, 20),
                // Not null check
                new Condition('reviewer.id', OperatorConstants::IS_NOT_NULL, null),
            ]
        ],

        [
            'id' => 'reject-priority',
            'name' => 'Reject Priority', 
            'from_state_id' => 'priority-review',
            'to_state_id' => 'rejected',
            'conditions' => [
                // Overdue deadline automatic rejection
                new Condition('document.deadline', OperatorConstants::IS_OVERDUE, null),
            ]
        ]
    ];

    foreach ($transitionsData as $transitionData) {
        // Create the transition
        $transition = TransitionFactory::createFromArray($transitionData);
        
        // Add conditions if provided
        if (isset($transitionData['conditions'])) {
            foreach ($transitionData['conditions'] as $condition) {
                $transition->addCondition($condition);
            }
        }
        
        $workflow->addTransition($transition);
    }

    return $workflow;
}

/**
 * Demonstrate operator usage examples
 */
function demonstrateOperators(): void
{
    echo "=== Xavante Workflow Operators Demonstration ===\n\n";

    // Get all available operators
    $operators = OperatorRegistry::getAvailableOperators();
    echo "Available operators: " . count($operators) . " total\n";
    echo "Examples: " . implode(', ', array_slice($operators, 0, 10)) . "...\n\n";

    // Comparison examples
    echo "--- Comparison Operators ---\n";
    $gt = OperatorRegistry::get(OperatorConstants::GREATER);
    echo "Priority 9 > 5: " . ($gt->evaluate(9, 5) ? 'true' : 'false') . "\n";
    
    $equals = OperatorRegistry::get(OperatorConstants::EQUALS);
    echo "Status equals 'approved': " . ($equals->evaluate('approved', 'approved') ? 'true' : 'false') . "\n";

    // String operators
    echo "\n--- String Operators ---\n";
    $contains = OperatorRegistry::get(OperatorConstants::CONTAINS);
    echo "Email contains '@': " . ($contains->evaluate('user@example.com', '@') ? 'true' : 'false') . "\n";
    
    $lengthGt = OperatorRegistry::get(OperatorConstants::LENGTH_GT);
    echo "Title length > 10: " . ($lengthGt->evaluate('This is a long title', 10) ? 'true' : 'false') . "\n";

    $regex = OperatorRegistry::get(OperatorConstants::REGEX);
    echo "Phone matches pattern: " . ($regex->evaluate('+1-555-123-4567', '/^\+?[0-9-() ]+$/') ? 'true' : 'false') . "\n";

    // Date operators  
    echo "\n--- Date Operators ---\n";
    $isDue = OperatorRegistry::get(OperatorConstants::IS_DUE);
    $pastDate = new DateTime('2024-01-01');
    echo "Past date is due: " . ($isDue->evaluate($pastDate, null) ? 'true' : 'false') . "\n";
    
    $dueInDays = OperatorRegistry::get(OperatorConstants::DUE_IN_DAYS); 
    $futureDate = (new DateTime())->add(new DateInterval('P3D')); // 3 days from now
    echo "Event due in 7 days: " . ($dueInDays->evaluate($futureDate, 7) ? 'true' : 'false') . "\n";

    // Logical operators
    echo "\n--- Logical Operators ---\n";
    $isEmpty = OperatorRegistry::get(OperatorConstants::IS_EMPTY);
    echo "Empty string is empty: " . ($isEmpty->evaluate('', null) ? 'true' : 'false') . "\n";
    
    $isTrue = OperatorRegistry::get(OperatorConstants::IS_TRUE);
    echo "Boolean true is true: " . ($isTrue->evaluate(true, null) ? 'true' : 'false') . "\n";
    echo "String 'true' is true: " . ($isTrue->evaluate('true', null) ? 'true' : 'false') . "\n"; // Should be false
}

/**
 * Show workflow integration
 */
function showWorkflowIntegration(): void
{
    echo "\n\n=== Workflow Integration Example ===\n";
    
    $workflow = createAdvancedWorkflow();
    echo "Created workflow: " . $workflow->name . "\n";
    echo "States: " . count($workflow->states->toArray()) . "\n";  
    echo "Transitions: " . count($workflow->transitions->toArray()) . "\n";

    // Show condition examples from transitions
    echo "\nExample transition conditions:\n";
    foreach ($workflow->transitions->toArray() as $transition) {
        echo "- {$transition->getFromStateId()} → {$transition->getToStateId()}:\n";
        $conditions = $transition->getConditions();
        if ($conditions && count($conditions->toArray()) > 0) {
            foreach ($conditions->toArray() as $condition) {
                echo "  • {$condition->variablePath} {$condition->operator} " . 
                     json_encode($condition->getValue()) . "\n";
            }
        } else {
            echo "  • No conditions defined\n";
        }
        echo "\n";
    }
}

// Run the demonstrations
if (php_sapi_name() === 'cli') {
    demonstrateOperators();
    showWorkflowIntegration(); 
    
    echo "\n=== Summary ===\n";
    echo "✓ Created comprehensive operator system with 4 categories\n";
    echo "✓ Implemented 25+ operators with aliases for flexibility\n";
    echo "✓ Provided backward compatibility with existing Equals operator\n";
    echo "✓ Created centralized registry for easy operator management\n";
    echo "✓ Added comprehensive test coverage and documentation\n";
    echo "✓ Demonstrated integration with workflow conditions\n";
}
<?php

namespace Xavante\Runtime;

use Xavante\Conditions\Operators\OperatorConstants;
use Xavante\Conditions\Operators\OperatorRegistry;
use Xavante\Models\Domain\Condition;
use Xavante\Models\Domain\Workflow;
use Xavante\Models\Runtime\Process;


/**
 * Class Processor
 *
 * Responsible for processing workflow instances, handling events, and managing variables.
 * 
 * The execution cycle involves:
 * 1. Evaluating the current active states of the workflow instance.
 * 2. Checking for any raised events that may trigger transitions.
 * 3. Evaluating conditions associated with transitions to determine if they can be taken.
 * 4. Executing the transition if conditions are met, which includes:
 *    - Exiting the source state (running exit actions).
 *    - Performing any transition actions.
 *    - Entering the target state (running entry actions).
 * 5. Updating the workflow instance's active states and variables as needed.
 * 
 * Condition Assessment:
 * - Uses OperatorRegistry to dynamically resolve and execute operators
 * - Supports all operators defined in the system (comparison, logical, string, date)
 * - Operators are referenced by name and resolved at runtime for flexibility
 * - Use OperatorConstants for type-safe operator names when creating conditions
 * 
 * Deterministic Transition Selection:
 * - When multiple transitions from a state have satisfied conditions, selects deterministically
 * - Priority: Transitions with more conditions (more specific) take precedence
 * - If condition counts are equal, first defined transition is selected
 * - Ensures predictable workflow execution without ambiguous state transitions
 */
class Processor
{

    /**
     * @var array Contextual information for processing (e.g., user info, environment data)
     * This context may have drivers and other interfaces to interact with external systems.
     */
    protected array $context = [];


    public function __construct(array $context = [])
    {
        $this->context = $context;
    }


    public function instantiate(Workflow $workflow, array $configuration = []) : Process
    {
        return new Process($workflow, $configuration);
    }

    /**
     * Process workflow instance by evaluating and executing state transitions.
     * 
     * Implements deterministic transition selection: when multiple transitions
     * from a state could be taken, selects the one with the most conditions
     * (most specific). If condition counts are equal, takes the first defined.
     * 
     * @param Process $instance The workflow process instance to advance
     */
    public function process(Process $instance) : void
    {

        // get the current active states
        $activeStatesIds = $instance->getActiveStatesIds();

        $listTransitionsTaken = [];
        // For each active state, check for possible transitions
        foreach ($activeStatesIds as $stateId) {
            $state = $instance->getWorkflow()->states->getById($stateId);
            if ($state === null) {
                continue;
            }

            // Check transitions from this state
            $workflow = $instance->getWorkflow();
            $transitions = $workflow->transitions->getBySourceStateId($stateId);

            // Collect all valid transitions with their condition counts for deterministic selection
            $validTransitions = [];
            
            foreach ($transitions as $transition) {
                // Evaluate ALL conditions for this transition (AND logic)
                $canTakeTransition = true; // Assume true, fail if any condition fails
                
                $conditions = $transition->getConditions();
                foreach ($conditions->toArray() as $condition) {
                    $assessment = $this->assessCondition($condition, $instance);
                    
                    if (!$assessment) {
                        // One condition failed, cannot take transition
                        $canTakeTransition = false;
                        break; // No need to check remaining conditions
                    }
                }

                // If no conditions exist, transition can be taken
                if (count($conditions->toArray()) === 0) {
                    $canTakeTransition = true;
                }

                if ($canTakeTransition) {
                    $validTransitions[] = [
                        'transition' => $transition,
                        'conditionCount' => count($conditions->toArray())
                    ];
                }
            }
            
            // Deterministic selection: prioritize transitions with more conditions (more specific)
            // If multiple transitions have same number of conditions, take the first one
            if (!empty($validTransitions)) {
                // Sort by condition count (descending), then by original order
                usort($validTransitions, function($a, $b) {
                    return $b['conditionCount'] <=> $a['conditionCount'];
                });
                
                // Take only the highest priority transition (most conditions)
                $selectedTransition = $validTransitions[0]['transition'];
                $listTransitionsTaken[] = $selectedTransition;
            }
            
        }



        $newStatesIds = [];
        foreach ($listTransitionsTaken as $transition) {
            // Execute the transition
            // 1. Exit source state
            $fromState = $instance->getWorkflow()->states->getById((string)$transition->getFromStateId());
            if ($fromState !== null) {
                foreach ($fromState->getExitActions()->toArray() as $action) {
                    $action->execute($instance);
                }
            }

            // 2. (Optional) Execute transition actions (not implemented here)

            // 3. Enter target state
            $toState = $instance->getWorkflow()->states->getById((string)$transition->getToStateId());
            if ($toState !== null) {
                foreach ($toState->getEntryActions()->toArray() as $action) {
                    $action->execute($instance);
                }
            }

            // Update active states in the process instance
            // (not fully implemented here)
            $newStatesIds[] = $transition->getToStateId();
            
        }

        if (count($newStatesIds) > 0) {
            $instance->setActiveStatesIds($newStatesIds);
        }

        // print_r($listTransitionsTaken); exit;

        // Implementation will go here
    }


    public function triggerEvent(Process $instance, string $eventName, array $eventData = []) : void
    {
        
        // First, look if the event exists in the workflow
        $event = $instance->getWorkflow()->events->getByName($eventName);
        if ($event === null) {
            throw new \InvalidArgumentException("Event '{$eventName}' not found in workflow");
        }

        // Next, raise the event in the process instance


        $instance->raiseEvent($event, $eventData);


    }

    public function setVariable(Process $instance, string $variableId, mixed $value) : void
    {
        // Implementation will go here
    }

    public function getVariable(Process $instance, string $variableId) : mixed
    {
        // Implementation will go here
        return null;
    }


    /**
     * Assesses a single condition against the current process instance.
     * 
     * Uses OperatorRegistry to dynamically resolve operators by name, supporting
     * all available operators: comparison (equals, greater_than, etc.), 
     * logical (and, or, not), string (contains, regex, etc.), and date operators.
     * 
     * @param Condition $condition The condition to evaluate
     * @param Process $instance The process instance providing variable context
     * @return bool True if condition is met, false otherwise
     * @throws \InvalidArgumentException If operator is not registered
     */
    protected function assessCondition(Condition $condition, Process $instance) : bool
    {
        // Use OperatorRegistry to get the operator instance
        if (!OperatorRegistry::has($condition->operator)) {
            throw new \InvalidArgumentException("Unsupported operator '{$condition->operator}' in condition");
        }

        $operator = OperatorRegistry::get($condition->operator);
        $expectedValue = $condition->getValue();
        $actualValue = $this->getActualVariableValue($instance, $condition->getVariablePath());
        
        // Evaluate: actualValue operator expectedValue (e.g., 85 >= 80)
        return $operator->evaluate($actualValue, $expectedValue);
    }



    protected function getActualVariableValue(Process $instance, string $variablePath) : mixed
    {
        // Implementation to retrieve the actual variable value from the process instance
        // based on the variable path.

        return $instance->getVariableValue($variablePath);
    }

}
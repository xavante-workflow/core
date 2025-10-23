<?php

namespace Xavante\Runtime;

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


            // print_r($transitions); exit;



            foreach ($transitions as $transition) {
                // Evaluate conditions (not implemented here)
                $canTakeTransition = true; // Placeholder for condition evaluation

                $conditions = $transition->getConditions();
                foreach ($conditions->toArray() as $condition) {
                    // Evaluate each condition (not implemented)
                    // If all conditions are met, set $canTakeTransition to true
                    $assessment = $this->assessCondition($condition, $instance);
                    if (!$assessment) {
                        $canTakeTransition = false;
                        break;
                    } 
                }


                if ($canTakeTransition) {
                    $listTransitionsTaken[] = $transition;
                }
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

        $instance->setActiveStatesIds($newStatesIds);

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


    protected function assessCondition(Condition $condition, Process $instance) : bool
    {




        // Placeholder for condition assessment logic
        return true;
    }

}
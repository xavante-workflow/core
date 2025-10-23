<?php

namespace Xavante\Runtime;

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
        // Implementation will go here
    }


    public function triggerEvent(Process $instance, string $eventName, array $eventData = []) : void
    {
        // Implementation will go here
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

}
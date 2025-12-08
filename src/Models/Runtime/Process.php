<?php

namespace Xavante\Models\Runtime;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Types\Id;

/**
 * Class Process
 *
 * Represents a running instance of a Workflow, maintaining its state, variables, and history.
 */
class Process
{

    public readonly Id $id;


    protected Workflow $workflow;
    protected array $activeStatesIds = [];

    /**
     * Associative array to hold the variable Ids and their values
     */
    protected array $variables = [];


    protected array $raisedEvents = [];

    protected array $history = [];


    protected array $configuration = [];

    public function __construct(Workflow $workflow, array $configuration = [])
    {
        $this->id = new Id(null);
        $this->workflow = $workflow;
        $this->configuration = $configuration;

        $this->addToHistory('process_created', 'Process created');


        $this->activeStatesIds = $this->workflow->getInitialStatesIds(); 
        foreach ($this->workflow->variables as $variable) {
            $this->variables[(string) $variable->id] = $variable->getValue();
        }
    }


    public function getWorkflow() : ?Workflow
    {
        return $this->workflow;
    }



    public function getActiveStatesIds() : array
    {
        return $this->activeStatesIds;
    }

    public function getVariables() : array
    {
        return $this->variables;
    }

    public function raiseEvent($event, array $eventData = []) : void
    {
        $this->raisedEvents[] = [
            'event' => $event,
            'data' => $eventData,
            'timestamp' => new \DateTimeImmutable(),
        ];

        $this->addToHistory('event_raised', "Event '{$event->id}' raised");

       $event->trigger($this, $eventData);
    }


    public function getRaiseEvents() : array
    {
        return $this->raisedEvents;
    }



    public function setVariableValue(string $variableId, mixed $value) : void
    {
        $this->addToHistory('variable_set', "Variable '{$variableId}' set to value '" . json_encode($value, true) . "'");
        $this->variables[$variableId] = $value;
    }



    public function addToHistory(string $type, string $entry) : void
    {
        $this->history[] = [
            'type' => $type,
            'entry' => $entry,
            'timestamp' => new \DateTimeImmutable(),
        ];
    }



    public function getHistory() : array
    {
        return $this->history;
    }


    public function setActiveStatesIds(array $activeStatesIds) : void
    {
        $this->activeStatesIds = $activeStatesIds;
        $this->addToHistory('active_states_set', "Active states set to: " . implode(', ', $activeStatesIds));
    }

    public function getVariableValue(string $variableId) : mixed
    {
        return $this->variables[$variableId] ?? null;
    }
}
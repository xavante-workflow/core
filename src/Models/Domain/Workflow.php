<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Containers\Events;
use Xavante\Models\Types\Id;
use Xavante\Models\Types\Containers\States;
use Xavante\Models\Types\Containers\Transitions;
use Xavante\Models\Types\Containers\Variables;
use Xavante\Models\Types\Description;
use Xavante\Models\Types\Name;

/**
 * Class Workflow
 *
 * Represents a workflow consisting mostly of states and transitions.
 */
class Workflow implements \JsonSerializable
{
    /**
     * @var Id
     */
    public readonly Id $id;

    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'description' => (string) $this->description,
            'states' => $this->states->jsonSerialize(),
            'transitions' => $this->transitions->jsonSerialize(),
            'variables' => $this->variables->jsonSerialize(),
            'events' => $this->events->jsonSerialize(),
        ];
    }

    /**
     * @var Name
     */
    public readonly Name $name;

    /**
     * @var Description
     */
    public readonly Description $description;

    /**
     * List of states in the workflow
     * @var States
     */
    public readonly States $states;

    /**
     * List of transitions in the workflow
     * @var Transitions
     */
    public readonly Transitions $transitions;

    /**
     * Global variables for the workflow
     * @var Variables
     */
    public readonly Variables $variables;

    /**
     * List of events in the workflow
     * @var Events
     */
    public readonly Events $events;


    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->id = new Id($data['id'] ?? '');
        $this->name = new Name($data['name'] ?? '');
        $this->description = new Description($data['description'] ?? '');
        $this->states = new States();
        $this->transitions = new Transitions();
        $this->variables = new Variables();
        $this->events = new Events();
    }

    /**
     * @param State $state
     */
    public function addState(State $state): void
    {
        $this->states->addItem($state);
    }

    /**
     * @param Transition $transition
     */
    public function addTransition(Transition $transition): void
    {
        $this->transitions->addItem($transition);
    }

    /**
     * @param Variable $variable
     */
    public function addVariable(Variable $variable): void
    {
        $this->variables->addItem($variable);
    }

    /**
     * @param Event $event
     */
    public function addEvent(Event $event): void
    {
        $event->setWorkflow($this);
        $this->events->addItem($event);
    }


}
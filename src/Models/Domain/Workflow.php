<?php

namespace Xavante\Models\Domain;

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
class Workflow
{
    /**
     * @var Id
     */
    public readonly Id $id;

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
}
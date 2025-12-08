<?php

namespace Xavante\Models\Domain;

use Xavante\Actions\Actionable;
use Xavante\Models\Fixtures\HasWorkflowAccess;
use Xavante\Models\Runtime\Process;
use Xavante\Models\Types\Containers\Actions;
use Xavante\Models\Types\Id;
use Xavante\Models\Types\Name;

class Event implements \JsonSerializable
{

    use HasWorkflowAccess;

    /**
     * @var Id
     * The unique identifier for the event.
     */
    public readonly Id $id;

    /**
     * @var Name
     * The name of the event.
     */
    public readonly Name $name;

    /**
     * @var Actions
     * The actions associated with the event.
     */
    public readonly Actions $actions;

    

    /**
     * @param string|null $id
     * @param string $name
     */
    public function __construct(?string $id, string $name)
    {
        $this->id = new Id($id);
        $this->name = new Name($name);
        $this->actions = new Actions();
    }

    /**
     * Adds an action to the event.
     *
     * @param Actionable $action
     * @return void
     */
    public function addAction(Actionable $action): void
    {
        if ($this->getWorkflowInstance() !== null) {
            $action->setWorkflow($this->getWorkflowInstance());
            $action->setCaller($this);
        }        
        $this->actions->addItem($action);        
    }

    /**
     * Serializes the event to JSON.
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode([
            'id' => (string) $this->id->getId(),
            'name' => (string) $this->name->getName(),
            'actions' => $this->actions->jsonSerialize(),
        ]);
    }

    public function trigger(Process $process, mixed ...$args): void
    {
        foreach ($this->actions->toArray() as $action) {
            $action->execute($process, ...$args);
        }
    }
};
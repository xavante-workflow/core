<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Id;
use Xavante\Models\Types\Name;
use Xavante\Models\Types\Containers\Conditions;

/**
 * Class Transition
 *
 * Represents a transition between two states within a workflow.
 */
class Transition implements \JsonSerializable
{
    /**
     * @var Id
     * The unique identifier for the transition.
     */
    public readonly Id $id;

    /**
     * @var Name
     * The name of the transition.
     */
    public readonly Name $name;

    /**
     * @var Id
     * The unique identifier for the state the transition is coming from.
     */
    protected Id $fromStateId;

    /**
     * @var Id
     * The unique identifier for the state the transition is going to.
     */
    protected Id $toStateId;


    /**
     * @var Conditions
     * The conditions that must be met for the transition to occur.
     */
    protected Conditions $conditions;




    /**
     * @param string|null $id
     * @param string $name
     * @param string $fromStateId
     * @param string $toStateId
     */
    public function __construct(?string $id, string $name, string $fromStateId, string $toStateId)
    {
        $this->id = new Id($id);
        $this->name = new Name($name);
        $this->fromStateId = new Id($fromStateId);
        $this->toStateId = new Id($toStateId);
        $this->conditions = new Conditions();
    }

    /**
     * @param string $fromStateId
     * @return Transition
     */
    public function setFromId(string $fromStateId): Transition
    {
        $this->fromStateId = new Id($fromStateId);
        return $this;
    }

    /**
     * @param string $toStateId
     * @return Transition
     */
    public function setToId(string $toStateId): Transition
    {
        $this->toStateId = new Id($toStateId);
        return $this;
    }

    /**
     * @return string
     */
    public function getFromStateId(): string
    {
        return $this->fromStateId->id;
    }

    /**
     * @return string
     */
    public function getToStateId(): string
    {
        return $this->toStateId->id;
    }


    /**
     * @param Condition $condition
     * @return $this
     */
    public function addCondition(Condition $condition): self
    {
        $this->conditions->addItem($condition);
        return $this;
    }


    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode([
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'fromStateId' => (string) $this->fromStateId,
            'toStateId' => (string) $this->toStateId,
        ]);
    }

    /**
     * @param string $json
     * @return Transition
     */
    public static function jsonUnserialize(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            fromStateId: $data['fromStateId'] ?? '',
            toStateId: $data['toStateId'] ?? ''
        );
    }    
}
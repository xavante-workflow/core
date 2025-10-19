<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Id;
use Xavante\Models\Types\Name;

/**
 * Class State
 *
 * Represents a state within a workflow.
 */
class State implements \JsonSerializable
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
     * @param string|null $id
     * @param string $name
     */
    public function __construct(?string $id, string $name)
    {
        $this->id = new Id($id);
        $this->name = new Name($name);
    }


    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode([
            'id' => (string) $this->id,
            'name' => (string) $this->name,
        ]);
    }

    /**
     * @param string $json
     * @return State
     */
    public static function jsonUnserialize(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? ''
        );
    }

}

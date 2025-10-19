<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Description;
use Xavante\Models\Types\Id;

use Xavante\Models\Types\Name;


/**
 * Class Variable
 *
 * Represents a variable within a workflow, holding a value that can be used during execution.
 */
class Variable implements \JsonSerializable{
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
     * @var mixed
     */
    public readonly mixed $defaultValue;

    /**
     * @var mixed
     */
    public mixed $value = null;


    /**
     * @param string|null $id
     * @param string $name
     * @param string $description
     * @param mixed $defaultValue
     */
    public function __construct(?string $id, string $name, string $description, mixed $defaultValue = null)
    {
        $this->id = new Id($id);
        $this->name = new Name($name);
        $this->description = new Description($description);
        $this->defaultValue = $defaultValue;
    }

    /**
     * @param mixed $value
     * @return Variable
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value ?? $this->defaultValue;
    }

    /**
     * 
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode([
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'description' => (string) $this->description,
            'defaultValue' => $this->defaultValue,
            'value' => $this->getValue(),
        ]);
    }

    /**
     * @param string $json
     * @return Variable
     */
    public static function jsonUnserialize(string $json): self
    {
        $data = json_decode($json, true);
        $variable = new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            defaultValue: $data['defaultValue'] ?? null
        );
        if (array_key_exists('value', $data)) {
            $variable->setValue($data['value']);
        }
        return $variable;
    }


}
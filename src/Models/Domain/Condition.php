<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Id;
use Xavante\Models\Types\Name;

/**
 * Class Condition
 *
 * Represents a condition that must be met for a transition to occur.
 */
class Condition implements \JsonSerializable
{
    /**
     * @var string
     */
    public readonly string $variablePath;

    /**
     * @var string
     */
    public readonly string $operator;

    /**
     * @var mixed
     */
    public readonly mixed $value;

    public function __construct(string $variablePath, string $operator, mixed $value)
    {
        $this->variablePath = $variablePath;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function jsonSerialize(): string
    {
        return json_encode([
            'variablePath' => $this->variablePath,
            'operator' => $this->operator,
            'value' => $this->value,
        ]);
    }
}
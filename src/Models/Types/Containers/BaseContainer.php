<?php

namespace Xavante\Models\Types\Containers;


use Iterator;

abstract class BaseContainer implements Iterator
{
    protected array $items = [];
    protected int $position = 0;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function current(): mixed
    {
        return $this->items[$this->position] ?? null;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function addItem($item): void
    {
        $this->items[] = $item;
    }

    public function removeItem($item): void
    {
        $index = array_search($item, $this->items, true);
        if ($index !== false) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function fromArray(array $items): void
    {
        $this->items = $items;
        $this->rewind();
    }

    public function clear(): void
    {
        $this->items = [];
        $this->rewind();
    }

    public function __toString()
    {
        return json_encode($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function getById(string $id)
    {
        foreach ($this->items as $item) {            
            if (isset($item->id) && $item->id->isEqual($id)) {
                return $item;
            }
        }
        return null;
    }

    public function serialize(): string
    {
        return serialize($this->items);
    }

    public function unserialize($data): void
    {
        $this->items = unserialize($data, ['allowed_classes' => true]);
        $this->rewind();
    }


    public function hasId(string $id): bool
    {
        return $this->getById($id) !== null;
    }
}
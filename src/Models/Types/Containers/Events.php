<?php

namespace Xavante\Models\Types\Containers;

use Xavante\Models\Domain\Event;

class Events extends BaseContainer
{
    protected string $type = Event::class;


    public function getByName(string $id): ?Event
    {

        foreach ($this->items as $event) {
            if ((string) $event->id === $id) {
                return $event;
            }
        }
        return null;
    }
}
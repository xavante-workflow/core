<?php

namespace Xavante\Models\Types\Containers;

use Xavante\Models\Domain\Event;

class Events extends BaseContainer
{
    protected string $type = Event::class;
}
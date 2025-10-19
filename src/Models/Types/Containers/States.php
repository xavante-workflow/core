<?php

namespace Xavante\Models\Types\Containers;

use Xavante\Models\Domain\State;
use Xavante\Models\Factories\StatesFactory;

class States extends BaseContainer
{

    protected string $type = State::class;

    public function fromArray(array $items): void
    {
        $this->items = StatesFactory::createFromArray($items)->toArray();
        $this->rewind();
    }

}

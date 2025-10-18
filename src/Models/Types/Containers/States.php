<?php

namespace Xavante\Models\Types\Containers;

use Xavante\Models\Factories\StatesFactory;

class States extends BaseContainer
{
    public function fromArray(array $items): void
    {
        $this->items = StatesFactory::createFromArray($items)->toArray();
        $this->rewind();
    }

}

<?php

namespace Xavante\Models\Types\Containers;

use Xavante\Models\Domain\Transition;

class Transitions extends BaseContainer
{
    protected string $type = Transition::class;



    public function getBySourceStateId(string $stateId): array
    {
        $results = [];
        foreach ($this->items as $transition) {
            if ((string) $transition->getFromStateId() === $stateId) {
                $results[] = $transition;
            }
        }
        return $results;
    }
}

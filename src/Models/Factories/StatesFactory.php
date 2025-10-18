<?php

namespace Xavante\Models\Factories;

use Xavante\Models\Domain\State;
use Xavante\Models\Types\Containers\States;

class StatesFactory
{
    public static function createFromArray(array $statesList): States
    {
        $states = new States();
        foreach ($statesList as $stateData) {
            if (is_array($stateData))   {
                $stateData = StateFactory::createFromArray($stateData);
            } elseif (!$stateData instanceof State) {
                throw new \InvalidArgumentException('Invalid state data provided');
            }
            $states->addItem($stateData);
        }
        return $states;
    }
}

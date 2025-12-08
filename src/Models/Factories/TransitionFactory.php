<?php

namespace Xavante\Models\Factories;

use Xavante\Models\Domain\Transition;

class TransitionFactory
{
    public static function createFromArray(array $data): Transition
    {
        return new Transition(
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['from_state_id'] ?? '',
            $data['to_state_id'] ?? ''
        );
    }
}
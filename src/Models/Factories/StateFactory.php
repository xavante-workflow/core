<?php

namespace Xavante\Models\Factories;

use Xavante\Models\Domain\State;

class StateFactory
{
    public static function createFromArray(array $data): State
    {
        return new State(
            $data['id'] ?? '',
            $data['name'] ?? ''
        );
    }
}

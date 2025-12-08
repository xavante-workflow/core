<?php

namespace Xavante\Models\Factories;


use Xavante\Models\Domain\Variable;

class VariableFactory
{
    public static function create(?string $id, string $name, string $description='', mixed $defaultValue=null): Variable
    {
        return new Variable(
            id: $id,
            name: $name,
            description: $description,
            defaultValue: $defaultValue
        );
    }
}
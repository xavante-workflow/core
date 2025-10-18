<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Id;
use Xavante\Models\Types\Name;

class State
{
    public readonly Id $id;
    public readonly Name $name;

    public function __construct(?string $id, string $name)
    {
        $this->id = new Id($id);
        $this->name = new Name($name);
    }
}

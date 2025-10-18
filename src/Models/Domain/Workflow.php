<?php

namespace Xavante\Models\Domain;

use Xavante\Models\Types\Id;
use Xavante\Models\Types\Containers\States;
use Xavante\Models\Types\Description;
use Xavante\Models\Types\Name;

class Workflow
{
    public readonly Id $id;
    public readonly Name $name;
    public readonly Description $description;
    public readonly States $states;


    public function __construct(array $data = [])
    {
        $this->id = new Id($data['id']);
        $this->name = new Name($data['name'] ?? '');
        $this->description = new Description($data['description'] ?? '');
        $this->states = new States();
    }

    public function addState(State $state): void
    {
        $this->states->addItem($state);
    }



}
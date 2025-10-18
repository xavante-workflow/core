<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Factories\StateFactory;
class StateTest extends \PHPUnit\Framework\TestCase
{
    public function testStateCreationFromArray()
    {
        $stateData = ['id' => 'state1', 'name' => 'State 1'];

        $state = StateFactory::createFromArray($stateData);

        $this->assertEquals($stateData['id'], (string) $state->id);
        $this->assertEquals($stateData['name'], (string) $state->name);
    }


    public function testStateCreationWithoutId() {
        $stateData = ['name' => 'State 1'];

        $state = StateFactory::createFromArray($stateData);

        $id = (string) $state->id;
        $this->assertNotEmpty($id);
        $this->assertEquals($stateData['name'], (string) $state->name);
    }
}
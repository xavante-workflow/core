<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Domain\State;
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

    public function testStateJsonSerialization()
    {
        $stateData = ['id' => 'state1', 'name' => 'State 1'];

        $state = StateFactory::createFromArray($stateData);

        $json = $state->jsonSerialize();
        $this->assertJson($json);
        $this->assertIsString($json);

        $decoded = State::jsonUnserialize($json);
        $this->assertEquals($stateData['id'], (string) $decoded->id);
        $this->assertEquals($stateData['name'], (string) $decoded->name);
    }
}
<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Domain\Transition;

class TransitionTest extends \PHPUnit\Framework\TestCase
{
    public function testTransitionCreationFromArray()
    {
        $transitionData = [
            'id' => 'transition1',
            'name' => 'Transition 1',
            'from_state_id' => 'state1',
            'to_state_id' => 'state2',
        ];

        $transition = \Xavante\Models\Factories\TransitionFactory::createFromArray($transitionData);

        $this->assertEquals($transitionData['id'], (string) $transition->id);
        $this->assertEquals($transitionData['name'], (string) $transition->name);
        $this->assertEquals($transitionData['from_state_id'], (string) $transition->getFromStateId());
        $this->assertEquals($transitionData['to_state_id'], (string) $transition->getToStateId());
    }


    public function testSerializationAndDeserialization()
    {
        $transitionData = [
            'id' => 'transition1',
            'name' => 'Transition 1',
            'from_state_id' => 'state1',
            'to_state_id' => 'state2',
        ];

        $transition = \Xavante\Models\Factories\TransitionFactory::createFromArray($transitionData);

        $json = $transition->jsonSerialize();
        $this->assertJson($json);
        $this->assertIsString($json);

        $decodedData = Transition::jsonUnserialize($json);
        $this->assertEquals($transitionData['id'], (string) $decodedData->id);
        $this->assertEquals($transitionData['name'], (string) $decodedData->name);
        $this->assertEquals($transitionData['from_state_id'], (string) $decodedData->getFromStateId());
        $this->assertEquals($transitionData['to_state_id'], (string) $decodedData->getToStateId());
    }


}
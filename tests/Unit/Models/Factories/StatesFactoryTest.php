<?php

namespace Tests\Unit\Models\Factories;

use Xavante\Models\Factories\StatesFactory;
use Xavante\Models\Factories\StateFactory;

class StatesFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testStatesContainerCreationUsingFromArray()
    {
        $statesData = [
            ['id' => 'state1', 'name' => 'State 1'],
            ['id' => 'state2', 'name' => 'State 2'],
        ];

        $statesContainer = StatesFactory::createFromArray($statesData);

        $this->assertCount(2, $statesContainer->toArray());
        $this->assertEquals($statesData[0]['id'], $statesContainer->getById('state1')->id);
        $this->assertEquals($statesData[1]['name'], $statesContainer->getById('state2')->name);
    }


    public static function providerTestStatesContainerCreationUsingStateInstances() {

        return [
            [[['id' => 'state1', 'name' => 'State 1']], 1],
            [ [['id' => 'state2', 'name' => 'State 2']], 1 ],

            [[ ['id' => 'state1', 'name' => 'State 1'],
              ['id' => 'state2', 'name' => 'State 2'],
              ['id' => 'state3', 'name' => 'State 3']], 3
            ]

        ];
    }

    /**
     * @dataProvider providerTestStatesContainerCreationUsingStateInstances
     */
    public function testStatesContainerCreationUsingStateInstances(array $statesData, int $count)
    {
        $statesContainer = StatesFactory::createFromArray($statesData);

        $this->assertCount($count, $statesContainer->toArray());
        
        foreach ($statesData as $stateData) {
            $this->assertTrue($statesContainer->hasId($stateData['id']));
            $state = $statesContainer->getById($stateData['id']);
            $this->assertIsObject($state);
            $this->assertSame($stateData['id'], (string) $state->id);
            $this->assertSame($stateData['name'], (string) $state->name);
        }   

    }


}
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


    public function testStatesContainerCreationUsingStateInstances()
    {
        $state1 = StateFactory::createFromArray(['id' => 'state1', 'name' => 'State 1']);
        $state2 = StateFactory::createFromArray(['id' => 'state2', 'name' => 'State 2']);

        $statesData = [$state1, $state2];

        $statesContainer = StatesFactory::createFromArray($statesData);
        $this->assertCount(2, $statesContainer->toArray());
        $this->assertSame($state1, $statesContainer->getById('state1'));
        $this->assertSame($state2, $statesContainer->getById('state2'));
    }


}
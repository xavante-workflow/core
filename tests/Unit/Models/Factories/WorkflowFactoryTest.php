<?php

namespace Tests\Unit\Models\Factories;

use Xavante\Models\Factories\StateFactory;
use Xavante\Models\Factories\WorkflowFactory;

class WorkflowFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateSimpleWorkflow()
    {

        $data = [
            'id' => 'workflow1',
            'name' => 'Test Workflow',
            'description' => 'A workflow for testing purposes',
        ];

        $workflow = WorkflowFactory::createWorkflow($data);

        $this->assertInstanceOf(\Xavante\Models\Domain\Workflow::class, $workflow);
        $this->assertEquals('workflow1', $workflow->id->id);
        $this->assertEquals('Test Workflow', $workflow->name);
        $this->assertEquals('A workflow for testing purposes', $workflow->description);
    }


    public function testCreateWorkflowWithStates()
    {
        $data = [
            'id' => 'workflow2',
            'name' => 'Workflow with States',
            'description' => 'Testing workflow with states',
            'states' => [
                ['id' => 'state1', 'name' => 'State 1'],
                ['id' => 'state2', 'name' => 'State 2'],
            ],
        ];

        $workflow = WorkflowFactory::createWorkflow($data);

        $this->assertInstanceOf(\Xavante\Models\Domain\Workflow::class, $workflow);
        $this->assertCount(2, $workflow->states->toArray());
        $this->assertEquals('state1', $workflow->states->getById('state1')->id);
        $this->assertEquals('State 2', $workflow->states->getById('state2')->name);
    }
}
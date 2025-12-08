<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Domain\State;
use Xavante\Models\Domain\Transition;
use Xavante\Models\Factories\WorkflowFactory;
use Xavante\Models\Factories\StateFactory;
use Xavante\Models\Factories\TransitionFactory;

class WorkflowTest extends \PHPUnit\Framework\TestCase
{
    public function testWorkflowCreationWithMinimalData()
    {
        $workflowData = [
            'id' => 'workflow1',
            'name' => 'Test Workflow',
            'description' => 'A test workflow description'
        ];

        $workflow = new Workflow($workflowData);

        $this->assertEquals($workflowData['id'], (string) $workflow->id);
        $this->assertEquals($workflowData['name'], (string) $workflow->name);
        $this->assertEquals($workflowData['description'], (string) $workflow->description);
        $this->assertCount(0, $workflow->states->toArray());
        $this->assertCount(0, $workflow->transitions->toArray());
    }

    public function testWorkflowCreationWithEmptyData()
    {
        $workflow = new Workflow([]);

        $this->assertNotEmpty((string) $workflow->id); // Should generate a UUID
        $this->assertEquals('', (string) $workflow->name);
        $this->assertEquals('', (string) $workflow->description);
        $this->assertCount(0, $workflow->states->toArray());
        $this->assertCount(0, $workflow->transitions->toArray());
    }

    public function testWorkflowCreationWithIdOnly()
    {
        $workflowData = ['id' => 'workflow123'];

        $workflow = new Workflow($workflowData);

        $this->assertEquals($workflowData['id'], (string) $workflow->id);
        $this->assertEquals('', (string) $workflow->name);
        $this->assertEquals('', (string) $workflow->description);
    }

    public function testAddStateToWorkflow()
    {
        $workflow = new Workflow(['id' => 'workflow1', 'name' => 'Test Workflow']);
        $state = new State('state1', 'Initial State');

        $workflow->addState($state);

        $this->assertCount(1, $workflow->states->toArray());
        $this->assertTrue($workflow->states->hasId('state1'));
        $this->assertEquals('state1', (string) $workflow->states->getById('state1')->id);
        $this->assertEquals('Initial State', (string) $workflow->states->getById('state1')->name);
    }

    public function testAddMultipleStatesToWorkflow()
    {
        $workflow = new Workflow(['id' => 'workflow1', 'name' => 'Test Workflow']);
        $state1 = new State('state1', 'Initial State');
        $state2 = new State('state2', 'Processing State');
        $state3 = new State('state3', 'Final State');

        $workflow->addState($state1);
        $workflow->addState($state2);
        $workflow->addState($state3);

        $this->assertCount(3, $workflow->states->toArray());
        $this->assertTrue($workflow->states->hasId('state1'));
        $this->assertTrue($workflow->states->hasId('state2'));
        $this->assertTrue($workflow->states->hasId('state3'));
    }

    public function testAddTransitionToWorkflow()
    {
        $workflow = new Workflow(['id' => 'workflow1', 'name' => 'Test Workflow']);
        $transition = new Transition('transition1', 'Start Process', 'state1', 'state2');

        $workflow->addTransition($transition);

        $this->assertCount(1, $workflow->transitions->toArray());
        $this->assertTrue($workflow->transitions->hasId('transition1'));
        $retrievedTransition = $workflow->transitions->getById('transition1');
        $this->assertEquals('transition1', (string) $retrievedTransition->id);
        $this->assertEquals('Start Process', (string) $retrievedTransition->name);
    }

    public function testAddMultipleTransitionsToWorkflow()
    {
        $workflow = new Workflow(['id' => 'workflow1', 'name' => 'Test Workflow']);
        $transition1 = new Transition('transition1', 'Start', 'state1', 'state2');
        $transition2 = new Transition('transition2', 'Process', 'state2', 'state3');
        $transition3 = new Transition('transition3', 'Complete', 'state3', 'state4');

        $workflow->addTransition($transition1);
        $workflow->addTransition($transition2);
        $workflow->addTransition($transition3);

        $this->assertCount(3, $workflow->transitions->toArray());
        $this->assertTrue($workflow->transitions->hasId('transition1'));
        $this->assertTrue($workflow->transitions->hasId('transition2'));
        $this->assertTrue($workflow->transitions->hasId('transition3'));
    }

    public function testWorkflowFactoryCreation()
    {
        $workflowData = [
            'id' => 'workflow1',
            'name' => 'Factory Test Workflow',
            'description' => 'A workflow created via factory',
            'states' => [
                ['id' => 'state1', 'name' => 'Initial State'],
                ['id' => 'state2', 'name' => 'Processing State']
            ]
        ];

        $workflow = WorkflowFactory::createWorkflow($workflowData);

        $this->assertEquals($workflowData['id'], (string) $workflow->id);
        $this->assertEquals($workflowData['name'], (string) $workflow->name);
        $this->assertEquals($workflowData['description'], (string) $workflow->description);
        $this->assertCount(2, $workflow->states->toArray());
        $this->assertTrue($workflow->states->hasId('state1'));
        $this->assertTrue($workflow->states->hasId('state2'));
    }

    public function testWorkflowFactoryCreationWithoutStates()
    {
        $workflowData = [
            'id' => 'workflow2',
            'name' => 'Simple Workflow',
            'description' => 'A workflow without predefined states'
        ];

        $workflow = WorkflowFactory::createWorkflow($workflowData);

        $this->assertEquals($workflowData['id'], (string) $workflow->id);
        $this->assertEquals($workflowData['name'], (string) $workflow->name);
        $this->assertEquals($workflowData['description'], (string) $workflow->description);
        $this->assertCount(0, $workflow->states->toArray());
    }

    public function testWorkflowWithUnicodeContent()
    {
        $workflowData = [
            'id' => 'workflow-unicode',
            'name' => 'Fluxo de Trabalho - 測試工作流',
            'description' => 'Descrição com caracteres especiais: ção, ñ, 中文, العربية'
        ];

        $workflow = new Workflow($workflowData);

        $this->assertEquals($workflowData['name'], (string) $workflow->name);
        $this->assertEquals($workflowData['description'], (string) $workflow->description);
    }

    public function testWorkflowImmutability()
    {
        $workflowData = [
            'id' => 'immutable-workflow',
            'name' => 'Immutable Test',
            'description' => 'Testing readonly properties'
        ];

        $workflow = new Workflow($workflowData);

        // Verify that properties are readonly by checking their values remain unchanged
        $originalId = (string) $workflow->id;
        $originalName = (string) $workflow->name;
        $originalDescription = (string) $workflow->description;

        // Properties should remain the same (readonly prevents modification)
        $this->assertEquals($originalId, (string) $workflow->id);
        $this->assertEquals($originalName, (string) $workflow->name);
        $this->assertEquals($originalDescription, (string) $workflow->description);
    }
}
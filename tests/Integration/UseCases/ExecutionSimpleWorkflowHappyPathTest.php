<?php

namespace Tests\Integration\UseCases;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Xavante\Models\Domain\Workflow;
use Xavante\Models\Runtime\Process;
use Xavante\Runtime\Processor;

class ExecutionSimpleWorkflowHappyPathTest extends TestCase
{

    protected static Workflow $workflow;
    protected static ?Process $process = null;
    protected static Processor $processor;

    public static function setUpBeforeClass(): void
    {
        // code
        self::$workflow = require __DIR__ . '/1-simple-workflow.php';
        $context = [];
        self::$processor = new Processor($context);

    }
    



    public function testWorkflowJustInstantiation(): void
    {

        // The process should be null initially
        $this->assertNull(self::$process);


        self::$process = self::$processor->instantiate(self::$workflow);

        // The process should be in the draft state
        $this->assertNotNull(self::$process);
        $this->assertIsObject(self::$process);
        $this->assertInstanceOf(Process::class, self::$process);


    }

    #[Depends('testWorkflowJustInstantiation')]
    public function testWorkflowJustInstantiatedShouldBeStateDraft(): void
    {
        $expectedActiveStates = ['id:draft'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);
    }



    #[Depends('testWorkflowJustInstantiatedShouldBeStateDraft')]
    public function testEventSubmitWillSetDocumentStatusToSubmitted(): void
    {
        $eventId = 'id:document_submitted';
        $variableId = 'document.status';


        // Trigger the document_submitted event
        self::$processor->triggerEvent(self::$process, $eventId);

        // Verify that the variable document.status is set to 'submitted'
        $variables = self::$process->getVariables();
        $this->assertArrayHasKey($variableId, $variables);
        $this->assertEquals('submitted', $variables[$variableId]);

        $this->assertIsArray(self::$process->getRaiseEvents());
        $this->assertCount(1, self::$process->getRaiseEvents());

        // Verify that the raised event is document_submitted
        $this->assertEquals(self::$workflow->events->getById($eventId), self::$process->getRaiseEvents()[0]['event']);

        foreach (self::$process->getHistory() as $historyEntry) {
            if (isset($historyEntry['type']) ) {

                match ($historyEntry['type']) {
                    'event_raised' => $this->assertStringContainsString($eventId, $historyEntry['entry']),
                    'variable_set' => $this->assertStringContainsString($variableId, $historyEntry['entry']),
                    default => null,
                };
            }
        }

        // At this moment the state change has not happened yet, only the event was raised and the action executed
        $expectedActiveStates = ['id:draft'];

        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);

        //print_r(self::$process->getHistory()); exit;

    }


    #[Depends('testEventSubmitWillSetDocumentStatusToSubmitted')]
    public function testProcessTheWorkflowShouldMoveToPendingApprovalState(): void
    {
        // Process the workflow
        self::$processor->process(self::$process);

        // Verify that the active state is now 'pending-approval'
        $expectedActiveStates = ['id:pending-approval'];
        $activeStates = self::$process->getActiveStatesIds();
        $this->assertEquals($expectedActiveStates, $activeStates);
        print_r(self::$process->getHistory()); exit;
    }


}
    
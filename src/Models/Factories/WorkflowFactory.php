<?php

namespace Xavante\Models\Factories;

use Xavante\Models\Domain\State;
use Xavante\Models\Domain\Workflow;

class WorkflowFactory
{
    public static function createWorkflow(array $data): Workflow
    {
        $workflow = new Workflow($data);

        if (isset($data['states']) && is_array($data['states'])) {
            foreach ($data['states'] as $stateData) {
                $state = StateFactory::createFromArray($stateData);
                $workflow->addState($state);
            }
        }

        return $workflow;
    }
}

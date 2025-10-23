<?php

namespace Xavante\Models\Fixtures;

use Xavante\Models\Domain\Workflow;

trait HasWorkflowAccess
{


    protected ?Workflow $workflow = null;


    /**
     * @return \Xavante\Models\Domain\Workflow
     */
    public function getWorkflowInstance(): ?\Xavante\Models\Domain\Workflow
    {
        return $this->workflow;
    }

    /**
     * @param Workflow $workflow
     * @return void
     */
    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }
}
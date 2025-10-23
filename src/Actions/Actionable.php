<?php

namespace Xavante\Actions;

use Xavante\Models\Domain\Workflow;

interface Actionable
{
    public function setWorkflow(Workflow $workflow): void;
    public function configure(mixed ...$args): void;
    public function execute(mixed ...$args): void;
}

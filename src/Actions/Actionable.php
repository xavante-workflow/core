<?php

namespace Xavante\Actions;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Runtime\Process;

interface Actionable
{
    public function setWorkflow(Workflow $workflow): void;
    public function configure(mixed ...$args): void;
    public function execute(Process $process, mixed ...$args): void;
    public function setCaller(mixed $caller): void;
}

<?php

namespace Xavante\Actions;

use Xavante\Models\Runtime\Process;
use Xavante\Models\Types\Description;
use Xavante\Models\Types\Id;

/**
 * Action that copies the value from one variable to another.
 */
class CopyVariableAction extends ActionBase 
{
    protected Id $id;
    protected Description $description;

    /**
     * @var string The path of the source variable to copy from.
     */
    protected string $sourceVariablePath;

    /**
     * @var string The path of the target variable to copy to.
     */
    protected string $targetVariablePath;

    /**
     * @param string|null $description Action description
     * @param string $sourceVariablePath Source variable path
     * @param string $targetVariablePath Target variable path
     */
    public function __construct(?string $description, string $sourceVariablePath, string $targetVariablePath)
    {
        parent::__construct();
        $this->description = new Description($description);
        $this->sourceVariablePath = $sourceVariablePath;
        $this->targetVariablePath = $targetVariablePath;
    }

    public function configure(mixed ...$args): void 
    {
        // No configuration needed
    }

    /**
     * Executes the action to copy variable value.
     *
     * @param Process $process The process instance
     * @param mixed ...$args Additional arguments
     * @return void
     */
    public function execute(Process $process, mixed ...$args): void
    {
        parent::execute($process, ...$args);

        // Get the source variable value
        $sourceValue = $process->getVariableValue($this->sourceVariablePath);
        
        // Set it to the target variable
        $process->setVariableValue($this->targetVariablePath, $sourceValue);
    }
}
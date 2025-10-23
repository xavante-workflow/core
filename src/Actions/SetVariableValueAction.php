<?php

namespace Xavante\Actions;

use Xavante\Models\Runtime\Process;
use Xavante\Models\Types\Containers\Variables;
use Xavante\Models\Types\Description;
use Xavante\Models\Types\Id;

class SetVariableValueAction extends ActionBase 
{

    protected Id $id;
    protected Description $description;

    /**
     * @var string
     * The path of the variable to set.
     */
    protected string $variablePath;

    /**
     * @var mixed
     * The value to set the variable to.
     */
    protected mixed $value;

    /**
     * @param string $variablePath
     * @param mixed $value
     */
    public function __construct(?string $description,string $variablePath, mixed $value)
    {
        parent::__construct();
        $this->description = new Description($description);
        $this->variablePath = $variablePath;
        $this->value = $value;
    }



    public function configure(mixed ...$args): void {

    }



    /**
     * Executes the action to set the variable value.
     *
     * @param Variables $variables
     * @return void
     */
    public function execute(Process $process, mixed ...$args): void
    {

        parent::execute($process, ...$args);

        // $workflow = $this->getWorkflowInstance();
        // Should have access to WF container to look bythe variable path and set the value

        $process->setVariableValue($this->variablePath, $this->value);

        // $variable = $args[0] ?? null;

        // if (!$variable instanceof Variables) {
        //     throw new \InvalidArgumentException('Expected an instance of Variables.');
        // }

        // $variable->setValue($this->variablePath, $this->value);
    }
}
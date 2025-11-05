<?php

namespace Xavante\Actions;

use Xavante\Models\Fixtures\HasWorkflowAccess;
use Xavante\Models\Runtime\Process;
use Xavante\Models\Types\Id;

abstract class ActionBase implements Actionable, \JsonSerializable
{
    use HasWorkflowAccess;

    protected Id $id;

    protected mixed $caller = null;

    public function setCaller(mixed $caller): void
    {
        $this->caller = $caller;
    }

    public function __construct()
    {
        $this->id = new Id(null);
    }

    /**
     * Serializes the action to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): string
    {

        $objectVars = get_object_vars($this);
        unset($objectVars['workflow']);

        return json_encode([
            'type' => static::class,
            'params' => $objectVars,
        ]);
    }


    public function execute(Process $process, mixed ...$args): void
    {
        $process->addToHistory(
            'action_executed',
            sprintf(
                "Action of type '%s' executed. (id=%s)",
                static::class, (string)$this->id
            )
        );
    }

}
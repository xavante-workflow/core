<?php

namespace Xavante\Actions;

use Xavante\Models\Fixtures\HasWorkflowAccess;

abstract class ActionBase implements Actionable, \JsonSerializable
{
    use HasWorkflowAccess;

    /**
     * Serializes the action to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {

        $objectVars = get_object_vars($this);
        unset($objectVars['workflow']);

        return [
            'type' => static::class,
            'params' => $objectVars,
        ];
    }

}
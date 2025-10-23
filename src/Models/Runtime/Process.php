<?php

namespace Xavante\Models\Runtime;

use Xavante\Models\Domain\Workflow;
use Xavante\Models\Types\Id;

/**
 * Class Process
 *
 * Represents a running instance of a Workflow, maintaining its state, variables, and history.
 */
class Process
{

    public readonly Id $id;


    protected Workflow $workflow;
    protected array $activeStatesIds = [];

    /**
     * Associative array to hold the variable Ids and their values
     */
    protected array $variables = [];


    protected array $raisedEvents = [];

    protected array $history = [];


    protected array $configuration = [];

    public function __construct(Workflow $workflow, array $configuration = [])
    {
        $this->id = new Id(null);
        $this->workflow = $workflow;
        $this->configuration = $configuration;
    }
}
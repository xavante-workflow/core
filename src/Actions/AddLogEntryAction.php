<?php

namespace Xavante\Actions;

use Xavante\Models\Runtime\Process;

class AddLogEntryAction extends ActionBase
{
    protected string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
    public function configure(mixed ...$args): void
    {
        // No configuration needed for this action
    }


    public function execute(Process $process, mixed ...$args): void
    {
        parent::execute($process, ...$args);
        // Assuming the process has a method to add log entries
        $process->addToHistory('audit', $this->message);
    }
    public function jsonSerialize(): string
    {
        return json_encode([
            'type' => static::class,
            'message' => $this->message,
        ]);
    }
}
<?php

namespace FiiSoft\Tools\TasksQueue;

interface CommandResolver
{
    /**
     * Get task capable to handle command.
     *
     * @param Command $command
     * @return Task|null
     */
    public function getTaskForCommand(Command $command);
    
    /**
     * Set minimal level of messages logged by logger.
     *
     * @param string $minLevel
     * @return void
     */
    public function setMinimalLogLevel($minLevel);
}
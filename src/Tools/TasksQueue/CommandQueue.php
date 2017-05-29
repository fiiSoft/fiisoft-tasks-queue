<?php

namespace FiiSoft\Tools\TasksQueue;

/**
 * Reads and returns from queue commands which are waiting for handle.
 */
interface CommandQueue
{
    /**
     * Wait until next new command is ready to be handled and then return it.
     *
     * @param bool $wait (default true) if true then it's blocking operation - waits for available command
     * @return Command|null can return null only if in non-blocking mode (param $wait is false)
     */
    public function getNextCommand($wait = true);
    
    /**
     * Confirm that this command has been handled.
     *
     * @param Command $command
     * @return void
     */
    public function confirmCommandHandled(Command $command);
    
    /**
     * Requeue command in case when its execution failed or it cannot be handled properly in this time.
     *
     * @param Command $command
     * @return mixed
     */
    public function requeueCommand(Command $command);
    
    /**
     * Publish command (send it to queue to execute by worker).
     *
     * @param Command $command
     * @return void
     */
    public function publishCommand(Command $command);
    
    /**
     * Get name of queue.
     *
     * @return string
     */
    public function queueName();
    
    /**
     * Set minimal level of messages logged by logger.
     *
     * @param string $minLevel
     * @return void
     */
    public function setMinimalLogLevel($minLevel);
}
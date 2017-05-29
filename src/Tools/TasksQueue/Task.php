<?php

namespace FiiSoft\Tools\TasksQueue;

use InvalidArgumentException;
use LogicException;

interface Task
{
    /**
     * Restart task with data from given Command to re-use this instance of Task to handle new Command.
     * Passed Command must be recognizable for the Task object.
     *
     * @param Command $command
     * @throws InvalidArgumentException when passed Command is unrecognizable for Task
     * @return void
     */
    public function restartWith(Command $command);
    
    /**
     * @return string
     */
    public function getName();
    
    /**
     * @return bool
     */
    public function isFinished();
    
    /**
     * @return void
     */
    public function execute();
    
    /**
     * Must return Command to continue job, but only if isFinished() returns true.
     * If method isFinished returns false then call this method must throws exception.
     *
     * @throws LogicException if is already finished
     * @return Command if is not finished yet
     */
    public function nextCommand();
}
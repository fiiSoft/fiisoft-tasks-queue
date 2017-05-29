<?php

namespace FiiSoft\Tools\TasksQueue\Task;

use FiiSoft\Tools\TasksQueue\Command;

final class NullTask extends AbstractTask
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->logDebug(
            'NullTask executed to handle command '.$this->command->getName().' ('.$this->command->getClassId().')'
        );
    }
    
    /**
     * @param Command $command
     * @return bool
     */
    protected function isCommandRecognisable(Command $command)
    {
        return true;
    }
}
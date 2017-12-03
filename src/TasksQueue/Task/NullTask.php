<?php

namespace FiiSoft\TasksQueue\Task;

use FiiSoft\TasksQueue\Command;

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
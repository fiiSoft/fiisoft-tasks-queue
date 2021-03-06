<?php

namespace FiiSoft\TasksQueue\Task;

use FiiSoft\Logger\SmartLogger;
use FiiSoft\TasksQueue\Command\TestingCommand;
use InvalidArgumentException;

final class TestingTask extends AbstractTask
{
    /** @var TestingCommand */
    protected $command;
    
    /**
     * @param TestingCommand $command
     * @param SmartLogger $logger
     * @throws InvalidArgumentException
     */
    public function __construct(TestingCommand $command, SmartLogger $logger)
    {
        parent::__construct($command, $logger);
    }
    
    protected function init()
    {
        $this->name = 'testingTask';
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        $message = 'Hello! from TestingTask';
        if ($this->command->getNumber()) {
            $message .= ' '.$this->command->getNumber();
        }
    
        $this->log($message, $this->command->getLevel());
    }
}
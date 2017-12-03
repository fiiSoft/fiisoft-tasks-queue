<?php

namespace FiiSoft\TasksQueue\Task;

use FiiSoft\Logger\SmartLogger;
use FiiSoft\Logger\SmartLogger\LoggedExceptionThrower;
use FiiSoft\TasksQueue\Command;
use FiiSoft\TasksQueue\Task;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

abstract class AbstractTask implements Task
{
    use LoggedExceptionThrower;
    
    /** @var Command */
    protected $command;
    
    /** @var string */
    protected $name;
    
    /** @var string */
    protected $jobUuid;
    
    /** @var array status of completion of particular steps */
    protected $status = [];
    
    /** @var array */
    private $statusCopy = [];
    
    /**
     * @param Command $command
     * @param SmartLogger $logger
     * @throws InvalidArgumentException
     */
    public function __construct(Command $command, SmartLogger $logger)
    {
        $this->logger = $logger;
     
        $this->init();
        $this->statusCopy = $this->status;
        
        $this->restartWith($command);
    }
    
    /**
     * @param Command $command
     * @throws InvalidArgumentException
     * @return void
     */
    final public function restartWith(Command $command)
    {
        if ($this->isCommandRecognisable($command)) {
            $this->command = $command;
            $this->status = array_fill_keys($this->statusCopy, false);
            $this->restoreState($this->command->getData());
        } else {
            throw new InvalidArgumentException(
                'It is forbidden to restart task '.$this->getName().' with command '.$command->getClassId()
            );
        }
    }
    
    /**
     * @param Command $command
     * @return bool
     */
    protected function isCommandRecognisable(Command $command)
    {
        return !$this->command || $command instanceof $this->command;
    }
    
    /**
     * @return void
     */
    protected function init()
    {
        //maybe some derived classes need it...
    }
    
    /**
     * @return string
     */
    final public function getName()
    {
        if ($this->name === null) {
            $segments = explode('\\', get_class($this));
            $name = array_pop($segments);
            
            if (0 === strpos($name, 'Task')) {
                $name = substr($this->name, strlen('Task'));
            }
            
            $this->name = $name;
        }
        
        return $this->name;
    }
    
    /**
     * @return bool
     */
    final public function isFinished()
    {
        foreach ($this->status as $status) {
            if (!$status) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @throws LogicException
     */
    final protected function assertNotFinished()
    {
        if ($this->isFinished()) {
            throw new LogicException('Task '.$this->getName().' is already finished');
        }
    }
    
    /**
     * Restore state of task from data supplied by command
     *
     * @param array $data
     * @return void
     */
    protected function restoreState(array $data)
    {
        if (isset($data['status'])) {
            $this->status = array_merge($this->status, $data['status']);
        }
    
        if (isset($data['jobUuid'])) {
            $this->jobUuid = $data['jobUuid'];
            $this->logContext['jobUuid'] = $data['jobUuid'];
        } else {
            $this->jobUuid = null;
            $this->logContext['jobUuid'] = null;
        }
    }
    
    /**
     * @return bool
     */
    final protected function finishIt()
    {
        foreach (array_keys($this->status) as $key) {
            $this->status[$key] = true;
        }
        
        return true;
    }
    
    /**
     * Must return Command to continue job, but only if isFinished() returns true.
     * If method isFinished returns false then call this method must throws exception.
     *
     * @throws LogicException if is already finished
     * @throws UnexpectedValueException
     * @return Command if is not finished yet
     */
    final public function nextCommand()
    {
        $this->assertNotFinished();
        
        $data = $this->prepareDataForNextCommand();
        if (!is_array($data)) {
            throw new UnexpectedValueException('Result returned from getDataForNextCommand must be an array!');
        }
        
        return $this->command->copyWithData(array_filter($data, function ($item) {
            return $item !== null;
        }));
    }
    
    /**
     * Must return data to build command send as next to continue job of this task.
     *
     * @return array
     */
    protected function prepareDataForNextCommand()
    {
        return [
            'status' => $this->status,
        ];
    }
}
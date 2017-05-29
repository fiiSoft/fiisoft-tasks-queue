<?php

namespace FiiSoft\Tools\TasksQueue\Handler;

use FiiSoft\Tools\Logger\Writer\SmartLogger;
use FiiSoft\Tools\TasksQueue\Command;
use FiiSoft\Tools\TasksQueue\CommandQueue;
use FiiSoft\Tools\TasksQueue\CommandResolver;
use FiiSoft\Tools\TasksQueue\Task;
use FiiSoft\Tools\TasksQueue\Task\NullTask;
use LogicException;

final class CommandHandler
{
    /** @var Task[] cached tasks to handle Commands */
    private $handlers = [];
    
    /** @var CommandResolver */
    private $commandResolver;
    
    /** @var CommandQueue */
    private $commandQueue;
    
    /** @var SmartLogger */
    private $logger;
    
    /** @var NullTask */
    private $nullTask;
    
    /** @var bool */
    private $runInProduction;
    
    /** @var bool */
    private $reuseTasks;
    
    /**
     * @param CommandResolver $commandResolver
     * @param CommandQueue $commandQueue
     * @param SmartLogger $logger
     * @param CommandHandlerConfig $config
     */
    public function __construct(
        CommandResolver $commandResolver,
        CommandQueue $commandQueue,
        SmartLogger $logger,
        CommandHandlerConfig $config
    ) {
        $this->commandResolver = $commandResolver;
        $this->commandQueue = $commandQueue;
        $this->logger = $logger;
        
        $this->logger->setPrefix('[H] ')->setContext(['source' => 'handler']);
        $this->runInProduction = $config->runInProduction;
        $this->reuseTasks = $config->reuseTasks;
    }
    
    /**
     * Set minimal level of messages logged by logger.
     *
     * @param string $minLevel
     * @return void
     */
    public function setMinimalLogLevel($minLevel)
    {
        $this->logger->setMinLevel($minLevel);
        $this->commandResolver->setMinimalLogLevel($minLevel);
        $this->commandQueue->setMinimalLogLevel($minLevel);
    }
    
    /**
     * @param Command $command
     * @throws LogicException
     * @return void
     */
    public function handle(Command $command)
    {
        $task = $this->getTask($command);
        $task->execute();
    
        if (!$task->isFinished()) {
            $this->commandQueue->publishCommand($task->nextCommand());
        }
    }
    
    /**
     * @param Command $command
     * @throws LogicException
     * @return Task
     */
    private function getTask(Command $command)
    {
        if ($this->reuseTasks) {
            $cacheId = $command->getClassId().'_'.$command->getVersion();
    
            if (isset($this->handlers[$cacheId])) {
                $task = $this->handlers[$cacheId];
                $task->restartWith($command);
                return $task;
            }
            
            $this->logger->notice('Create new handler for command '.$command->getName());
            $task = $this->commandResolver->getTaskForCommand($command);
            if ($task) {
                $this->handlers[$cacheId] = $task;
                return $task;
            }
        } else {
            $task = $this->commandResolver->getTaskForCommand($command);
            if ($task) {
                return $task;
            }
        }
    
        if ($this->runInProduction) {
            $message = 'Cannot determine which task should handle command ' . $command->getName();
            $this->logger->critical($message);
            throw new LogicException($message);
        }
        
        $this->logger->warning('NullTask returned to handle command '.$command->getName().'. Remember to fix it.');
        if ($this->nullTask) {
            $this->nullTask->restartWith($command);
        } else {
            $this->nullTask = new NullTask($command, $this->logger);
        }
        
        return $this->nullTask;
    }
}
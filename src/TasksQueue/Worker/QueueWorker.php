<?php

namespace FiiSoft\TasksQueue\Worker;

use Exception;
use FiiSoft\Logger\SmartLogger;
use FiiSoft\Logger\SmartLogger\SmartLoggerHolder;
use FiiSoft\TasksQueue\CommandQueue;
use FiiSoft\TasksQueue\Handler\CommandHandler;
use LogicException;
use RuntimeException;

final class QueueWorker
{
    use SmartLoggerHolder;
    
    /** @var CommandQueue */
    private $commandQueue;
    
    /** @var CommandHandler */
    private $commandHandler;
    
    /** @var QueueWorkerConfig */
    private $config;
    
    /** @var bool default setting */
    private $exitOnError;
    
    /**
     * @param CommandQueue $commandQueue
     * @param CommandHandler $commandHandler
     * @param SmartLogger $logger
     * @param QueueWorkerConfig $config
     */
    public function __construct(
        CommandQueue $commandQueue,
        CommandHandler $commandHandler,
        SmartLogger $logger,
        QueueWorkerConfig $config
    ) {
        $this->commandQueue = $commandQueue;
        $this->commandHandler = $commandHandler;
        $this->config = clone $config;
        $this->logger = $logger;
        
        $this->logContext['source'] = 'worker';
        $this->logger->setPrefix('[W] ')->setContext($this->logContext);
        $this->exitOnError = (bool) $config->exitOnError;
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
        $this->commandHandler->setMinimalLogLevel($minLevel);
        $this->commandQueue->setMinimalLogLevel($minLevel);
    }
    
    /**
     * Run worker in infinite loop and handle all incoming commands constantly.
     *
     * @param bool|null $exitOnError (default null) if true then worker will finish its job on first error
     * @param bool $breakIfQueueIsEmpty if true then worker will finish work if queue is empty
     * @throws RuntimeException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    public function run($exitOnError = null, $breakIfQueueIsEmpty = false)
    {
        $continue = !$breakIfQueueIsEmpty;
        while ($this->runOnce($continue, $exitOnError) || $continue);
    }
    
    /**
     * Handle next received command and return.
     * This is blocking operation when argument $wait is true (because it waits for next available command),
     * and non-blocking when $wait is false (if there is no command to handle, then simply returns).
     *
     * @param bool $wait (default true) if true then waits until command is available (blocking mode)
     * @param bool|null $exitOnError (default null) if true then worker will finish its job on any error
     * @throws LogicException
     * @throws RuntimeException
     * @throws Exception
     * @return bool true if command has been handled, false if there was no command in queue
     */
    public function runOnce($wait = true, $exitOnError = null)
    {
        $command = $this->commandQueue->getNextCommand($wait);
        if ($command) {
            $this->logActivity('Handle command: '.$command->getName());
            try {
                $this->commandHandler->handle($command);
            } catch (Exception $e) {
                $this->logCatchedException($e);
                
                $this->logNotice('Requeue command '.$command->getName().' after error');
                $this->commandQueue->requeueCommand($command);
                
                if ($exitOnError === true
                    || ($exitOnError === null && $this->exitOnError)
                    || $e->getCode() > 0
                ) {
                    throw $e;
                }
                
                return true;
            }
            
            $this->commandQueue->confirmCommandHandled($command);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * @param Exception $error
     * @throws RuntimeException
     * @return void
     */
    private function logCatchedException(Exception $error)
    {
        $this->logError('(Exception '.$error->getCode().'): '.$error->getMessage());
        
        $errorMsg = date('Y-m-d H:i:s').' ['.$error->getCode().'] '.$error->getMessage()
                    ."\n".'Stacktrace:'."\n".$error->getTraceAsString()."\n";
        
        $errorFile = $this->config->errorLogFile;
        if (false === file_put_contents($errorFile, $errorMsg, FILE_APPEND)) {
            throw new RuntimeException(
                'Unable to write to logfile '.$errorFile."\n".'Logged error is: '.$errorMsg
            );
        }
    }
    
    /**
     * @param string $message
     * @return void
     */
    private function logActivity($message)
    {
        $this->log($message, 'queue');
    }
}
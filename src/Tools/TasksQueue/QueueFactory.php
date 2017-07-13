<?php

namespace FiiSoft\Tools\TasksQueue;

use FiiSoft\Tools\Logger\Reader\LogsMonitor;
use FiiSoft\Tools\TasksQueue\Worker\QueueWorker;

interface QueueFactory
{
    /**
     * @return CommandQueue
     */
    public function CommandQueue();
    
    /**
     * @return LogsMonitor
     */
    public function LogsMonitor();
    
    /**
     * @return CommandQueue
     */
    public function InstantCommandQueue();
    
    /**
     * @return QueueWorker
     */
    public function InstantQueueWorker();
    
    /**
     * @return LogsMonitor
     */
    public function InstantLogsMonitor();
}
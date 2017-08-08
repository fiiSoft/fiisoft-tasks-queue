<?php

namespace FiiSoft\Tools\TasksQueue;

use FiiSoft\Tools\Logger\Reader\LogsMonitor;
use FiiSoft\Tools\TasksQueue\Worker\QueueWorker;

interface QueueFactory
{
    /**
     * If called with true as argument then factory will produce instant implementations of queue, worker and logger.
     * Instant implementations do not use external queue to operate, but in-memory, volatile and synchronous one.
     *
     * @param bool $bool
     * @return void
     */
    public function useInstantImplementations($bool = true);
    
    /**
     * @return CommandQueue
     */
    public function CommandQueue();
    
    /**
     * @return QueueWorker
     */
    public function QueueWorker();
    
    /**
     * @return LogsMonitor
     */
    public function LogsMonitor();
}
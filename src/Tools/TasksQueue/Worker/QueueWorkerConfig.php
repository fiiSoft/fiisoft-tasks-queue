<?php

namespace FiiSoft\Tools\TasksQueue\Worker;

use FiiSoft\Tools\Configuration\AbstractConfiguration;

final class QueueWorkerConfig extends AbstractConfiguration
{
    /**
     * If true then worker will end its job on first catch non-critical error.
     * If false then worker will requeue message and will continue working.
     * @var bool
     */
    public $exitOnError = false;
    
    /**
     * @var string path to file where errors will be logged
     */
    public $errorLogFile;
}
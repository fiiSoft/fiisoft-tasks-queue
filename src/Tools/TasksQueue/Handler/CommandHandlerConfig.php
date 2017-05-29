<?php

namespace FiiSoft\Tools\TasksQueue\Handler;

use FiiSoft\Tools\Configuration\AbstractConfiguration;

final class CommandHandlerConfig extends AbstractConfiguration
{
    /**
     * @var bool if true then handler knows that its working on production
     */
    public $runInProduction = false;
    
    /**
     * @var bool if true then each task will be cached and reused to handle each next command of the same type
     */
    public $reuseTasks = false;
}
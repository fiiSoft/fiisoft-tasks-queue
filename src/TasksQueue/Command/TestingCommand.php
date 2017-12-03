<?php

namespace FiiSoft\TasksQueue\Command;

use Psr\Log\LogLevel;

final class TestingCommand extends AbstractCommand
{
    /**
     * @param integer|null $number
     * @param string|null $level
     * @param string|null $jobUuid
     */
    public function __construct($number = null, $level = null, $jobUuid = null)
    {
        parent::__construct([
            'number' => $number,
            'level' => $level,
            'jobUuid' => $jobUuid,
        ]);
    }
    
    /**
     * @param string $default
     * @return string
     */
    public function getLevel($default = LogLevel::INFO)
    {
        return $this->getDataItem('level', $default);
    }
    
    /**
     * @return int|null
     */
    public function getNumber()
    {
        return $this->getDataItem('number');
    }
}
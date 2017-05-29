<?php

namespace FiiSoft\Tools\TasksQueue\Command;

final class StopRemovingTasks extends AbstractCommand
{
    /**
     * @param string $uuid any unique id
     */
    public function __construct($uuid = null)
    {
        parent::__construct(['uuid' => $uuid]);
    }
    
    /**
     * @param string $uuid
     * @return bool
     */
    public function hasUuid($uuid)
    {
        return isset($this->data['uuid']) && $this->data['uuid'] === $uuid;
    }
    
    /**
     * @return string|null
     */
    public function getUuid()
    {
        return isset($this->data['uuid']) ? $this->data['uuid'] : null;
    }
}
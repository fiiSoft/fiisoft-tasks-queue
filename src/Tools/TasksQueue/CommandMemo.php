<?php

namespace FiiSoft\Tools\TasksQueue;

use UnexpectedValueException;

/**
 * Object that carry all data required to proper restore command.
 * Be aware that it must be serializable, so all data stored in field data has to be serializable too.
 */
final class CommandMemo
{
    /** @var string */
    public $name;
    
    /** @var int */
    public $version = 1;
    
    /** @var string */
    public $classId;
    
    /** @var string */
    public $class;
    
    /** @var array */
    public $data = array();
    
    public function __wakeup()
    {
        if (!$this->isValid()) {
            throw new UnexpectedValueException('CommandMemo is in invalid state!');
        }
    }
    
    /**
     * @return bool
     */
    private function isValid()
    {
        if (!is_string($this->name) || !is_int($this->version) || !is_string($this->class) || !is_array($this->data)
            || $this->name === '' || $this->version < 1 || $this->class === ''
        ) {
            return false;
        }
    
        if ($this->version > 1 && (!is_string($this->classId) || $this->classId === '')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @throws UnexpectedValueException
     * @return Command
     */
    public function restoreCommand()
    {
        $command = new $this->class;
        if ($command instanceof Command) {
            $command->restoreFromMemo($this);
            return $command;
        }
        
        throw new UnexpectedValueException('Cannot restore Command from Memo');
    }
}
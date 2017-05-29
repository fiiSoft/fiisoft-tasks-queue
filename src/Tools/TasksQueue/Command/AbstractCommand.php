<?php

namespace FiiSoft\Tools\TasksQueue\Command;

use FiiSoft\Tools\TasksQueue\Command;
use FiiSoft\Tools\TasksQueue\CommandMemo;
use LogicException;

abstract class AbstractCommand implements Command
{
    /** @var string */
    protected $name;
    
    /** @var array */
    protected $data = [];
    
    /** @var int */
    protected $version = 2;
    
    /** @var string */
    protected $classId;
    
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    
    /**
     * @return string
     */
    final public function getName()
    {
        if ($this->name === null) {
            $this->name = $this->getClassId();
        }
        
        return $this->name;
    }
    
    /**
     * @return int
     */
    final public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * @return string
     */
    final public function getClassId()
    {
        if ($this->classId === null) {
            $segments = explode('\\', get_class($this));
            $this->classId = array_pop($segments);
        }
        
        return $this->classId;
    }
    
    /**
     * @param string $classId
     * @param int $version
     * @return bool
     */
    final public function is($classId, $version = 0)
    {
        if ($version > 0) {
            return $version === $this->getVersion() && $classId === $this->getClassId();
        }
        
        return $classId === $this->getClassId();
    }
    
    /**
     * @return CommandMemo
     */
    final public function getMemo()
    {
        $memo = new CommandMemo();
        
        $memo->name = $this->getName();
        $memo->version = $this->getVersion();
        $memo->classId = $this->getClassId();
        $memo->class = get_class($this);
        $memo->data = $this->getData();
        
        return $memo;
    }
    
    /**
     * @param CommandMemo $memo
     * @throws LogicException
     * @return void
     */
    final public function restoreFromMemo(CommandMemo $memo)
    {
        if (get_class($this) !== $memo->class) {
            throw new LogicException(
                'Cannot set internal state of Command from Memo because class does not match'
            );
        }
        
        $this->name = $memo->name;
        $this->version = $memo->version;
        $this->classId = $memo->classId;
        $this->data = $memo->data;
    }
    
    /**
     * @return array
     */
    final public function getData()
    {
        return $this->data;
    }
    
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    final protected function getDataItem($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    /**
     * @param array $data
     * @return static
     */
    final public function copyWithData(array $data)
    {
        $copy = clone $this;
        $copy->data = array_merge($copy->data, $data);
        return $copy;
    }
}
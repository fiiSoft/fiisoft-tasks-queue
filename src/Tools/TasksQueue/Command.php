<?php

namespace FiiSoft\Tools\TasksQueue;

interface Command
{
    /**
     * @return string
     */
    public function getName();
    
    /**
     * @return int
     */
    public function getVersion();
    
    /**
     * ClassId determines which Task should be used to handle Command.
     * But it does not have to be full class of Command - it can be any constant identifier.
     *
     * @return string
     */
    public function getClassId();
    
    /**
     * Tell if command has exactly the same classId (and version if given and greater then 0).
     *
     * @param string $classId
     * @param int $version (default 0 means do not check version)
     * @return bool
     */
    public function is($classId, $version = 0);
    
    /**
     * @return CommandMemo
     */
    public function getMemo();
    
    /**
     * Restore internal state of command from it's Memo.
     *
     * @param CommandMemo $memo
     * @return void
     */
    public function restoreFromMemo(CommandMemo $memo);
    
    /**
     * Get data stored by this command.
     * The data returned by this method are specific and understandable only by task that handle this command.
     *
     * @return array
     */
    public function getData();
    
    /**
     * Get copy of this command with some data changed (which come from task).
     *
     * @param array $data
     * @return static copy of command with new data set
     */
    public function copyWithData(array $data);
}
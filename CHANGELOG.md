# Changelog

All important changes to `fiisoft-tasks-queue` will be documented in this file

## 3.0.0

Interface QueueFactory rewritten (backward-incompatible change). 
This version is not tested yet. 

## 2.0.0

Added possibility to run queue-console-commands in "instant" mode.
 - added new interface QueueFactory
 - made changes in class AbstractQueueConsoleCommand - different params for constructor

## 1.2.0

Changes in QueueWorker (compatible backwards):
 - new parameter added to method run()
 - new parameter added to method runOnce()
 - type of method runOnce() changed from void to bool

## 1.1.0

Added new class AbstractQueueConsoleCommand.

## 1.0.0

Initial version of library.
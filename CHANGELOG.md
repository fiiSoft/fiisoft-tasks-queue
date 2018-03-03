# Changelog

All important changes to `fiisoft-tasks-queue` will be documented in this file

## 4.3.0

* New command queue:remove:all to remove all tasks from queue
* Command queue:remove:tasks shows number of removed tasks
* Every command has got optional parameter name in constructor to change default name

## 4.2.2

Console command QueueWorkerCommand is no longer final and can be extended.  

It has now new special method dispatch() that can be overrided, 
but this method still must be called from derived class to run or stop worker(s). 

## 4.2.1

Fixed bug of registering handlers for forbidden signals on Unix systems.  

Currently worker QueueWorkerCommand can handle signals: SIGINT, SIGTERM, SIGQUIT, SIGHUP, SIGTSTP 
(safely ends working). 

## 4.2.0

Parameter jobUuid is now generated and passed to command's factory method always.

## 4.1.0

Method AbstractQueueConsoleCommand::createQueueCommand can return array of Command objects now.

## 4.0.0

There are no new features in this version.
It is just backward-incompatible because all classes have been moved to other namespace.

## 3.2.0

Console command QueueTesterCommand is retrieving list of available levels directly from LogsMonitor.

## 3.1.0

Updated dependencies. Each method handleInput in each console command 
returns exit-code.

## 3.0.0

Interface QueueFactory rewritten (backward-incompatible change). 

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
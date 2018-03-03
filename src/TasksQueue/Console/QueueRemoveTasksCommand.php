<?php

namespace FiiSoft\TasksQueue\Console;

use FiiSoft\Tools\Console\AbstractCommand;
use FiiSoft\TasksQueue\Command\StopRemovingTasks;
use FiiSoft\TasksQueue\CommandQueue;
use FiiSoft\TasksQueue\CommandResolver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueRemoveTasksCommand extends AbstractCommand
{
    /** @var CommandQueue */
    private $commandQueue;
    
    /** @var CommandResolver */
    private $commandResolver;
    
    /**
     * @param CommandQueue $commandQueue
     * @param CommandResolver $commandResolver
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(CommandQueue $commandQueue, CommandResolver $commandResolver, $name = null)
    {
        parent::__construct($name ?: 'queue:remove:tasks');
        
        $this->commandQueue = $commandQueue;
        $this->commandResolver = $commandResolver;
    }
    
    protected function configure()
    {
        $this->setDescription('Remove tasks with given name from queue. Be sure there are no working workers!')
            ->setHelp('Command class: '.get_class($this));
    
        $this->addArgument('taskName', InputArgument::REQUIRED, 'Name of task to remove from queue');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return int
     */
    protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        $taskName = $input->getArgument('taskName');
        
        $stopCommand = new StopRemovingTasks($taskName);
        $this->writelnVV('Publish command '.$stopCommand->getName());
        $this->commandQueue->publishCommand($stopCommand);
        $countRemovedTasks = 0;
        
        do {
            $command = $this->commandQueue->getNextCommand(false);
            if ($command) {
                $this->writelnVVV('Command '.$command->getName().' found');
                
                if ($command instanceof StopRemovingTasks) {
                    if ($command->hasUuid($taskName)) {
                        $this->writelnVV('Command '.$command->getName().' has uuid '.$taskName.' - so break');
                        $this->commandQueue->confirmCommandHandled($command);
                        break;
                    }
                    
                    $this->writelnVV('Command '.$command->getName().' has uuid '.$command->getUuid().' - so continue');
                    $this->commandQueue->requeueCommand($command);
                    continue;
                }
                
                $task = $this->commandResolver->getTaskForCommand($command);
                if ($task) {
                    $this->writelnVVV('Check task '.$task->getName());
                    
                    if ($task->getName() === $taskName) {
                        $this->writelnV('Remove task '.$task->getName());
                        $this->commandQueue->confirmCommandHandled($command);
                        ++$countRemovedTasks;
                    } else {
                        $this->writelnVV('Requeue task '.$task->getName());
                        $this->commandQueue->requeueCommand($command);
                    }
                }
            }
        } while ($command);
    
        $this->writelnVV('Remove orphaned commands');
        
        do {
            $command = $this->commandQueue->getNextCommand(false);
            if ($command
                && $command instanceof StopRemovingTasks
                && $command->hasUuid($taskName)
            ) {
                $this->writelnVV('Removing command '.$command->getName());
                $this->commandQueue->confirmCommandHandled($command);
            }
        } while ($command);
        
        $this->writeln('Number of removed tasks: '.$countRemovedTasks);
        
        return 0;
    }
}
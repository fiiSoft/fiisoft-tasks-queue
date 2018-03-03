<?php

namespace FiiSoft\TasksQueue\Console;

use FiiSoft\TasksQueue\CommandQueue;
use FiiSoft\Tools\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueRemoveAllTasksCommand extends AbstractCommand
{
    /** @var CommandQueue */
    private $commandQueue;
    
    /**
     * @param CommandQueue $commandQueue
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(CommandQueue $commandQueue, $name = null)
    {
        parent::__construct($name ?: 'queue:remove:all');
        
        $this->commandQueue = $commandQueue;
    }
    
    protected function configure()
    {
        $this->setDescription('Remove ALL tasks from queue!')
            ->setHelp('Command class: '.get_class($this));
        
        $this->addOptionRun('Start command and remove all tasks from queue!');
        $this->addOption('wait', 'w', InputOption::VALUE_REQUIRED, 'Number of seconds to wait for new tasks in queue');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null null or 0 if everything went fine, or an error code
     */
    protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        if (!$this->canContinueExecution($input, $output)) {
            return 1;
        }
    
        $wait = (int) $input->getOption('wait');
        
        if ($wait > 0) {
            $this->writelnV('Wait max '.$wait.' seconds for new commands');
        } else {
            $wait = 0;
            $this->writelnV('Finish immediately when queue is empty');
        }
    
        $lastCheck = time();
        $count = 0;
        
        while (true) {
            $command = $this->commandQueue->getNextCommand(false);
            if ($command) {
                $this->writelnVVV('Remove command '.$command->getName());
                $this->commandQueue->confirmCommandHandled($command);
                $lastCheck = time();
                ++$count;
            } elseif (time() - $lastCheck >= $wait) {
                break;
            }
        }
    
        $this->writeln('Number of removed tasks: '.$count);
        
        return 0;
    }
}
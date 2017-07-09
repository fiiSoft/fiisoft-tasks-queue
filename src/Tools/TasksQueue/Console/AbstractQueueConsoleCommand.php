<?php

namespace FiiSoft\Tools\TasksQueue\Console;

use BadMethodCallException;
use FiiSoft\Tools\Console\AbstractCommand;
use FiiSoft\Tools\Logger\Reader\LogsMonitor;
use FiiSoft\Tools\OutputWriter\Adapter\SymfonyConsoleOutputWriter;
use FiiSoft\Tools\TasksQueue\Command;
use FiiSoft\Tools\TasksQueue\CommandQueue;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractQueueConsoleCommand extends AbstractCommand
{
    /** @var CommandQueue */
    private $commandQueue;
    
    /** @var LogsMonitor */
    private $logsMonitor;
    
    /**
     * @param string $name name of command
     * @param CommandQueue $commandQueue
     * @param LogsMonitor $logsMonitor
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct($name, CommandQueue $commandQueue, LogsMonitor $logsMonitor)
    {
        $this->commandQueue = $commandQueue;
        $this->logsMonitor = $logsMonitor;
        
        parent::__construct($name);
    
        if (!$this->getDefinition()->hasOption('monitor')) {
            $this->addOption('monitor', 'm', InputOption::VALUE_NONE, 'Display progress of execution');
        }
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     * @return void
     */
    final protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        if (!$this->canContinueExecution($input, $output)) {
            exit(10);
        }
    
        $jobUuid = null;
        $monitoringEnabled = $input->hasOption('monitor') && $input->getOption('monitor') && !$this->isQuiet();
        
        if ($monitoringEnabled) {
            if ($input->isInteractive()) {
                $this->writelnV('Monitoring of execution is enabled');
                $jobUuid = Uuid::uuid4()->toString();
            } else {
                $output->writeln('Monitoring is unavailable when no-interaction mode is enabled!');
                exit(100);
            }
        } else {
            $this->writelnV('Monitoring of execution is disabled');
        }
        
        $command = $this->createQueueCommand($input, $output, $jobUuid);
        
        if ($command instanceof Command) {
            $this->writelnV('Send command '.$command->getName().' to queue '.$this->commandQueue->queueName());
            $this->commandQueue->publishCommand($command);
        } else {
            $output->writeln('Command returned from method createQueueCommand is not of type '.Command::class);
            exit(50);
        }
    
        $this->displayInfoProcessStarted($output);
    
        if ($monitoringEnabled) {
            $output->writeln('To turn off displaying logs use CTRL+C');
        
            $this->logsMonitor
                ->setOutputWriter(new SymfonyConsoleOutputWriter($output))
                ->filterByContext(['jobUuid' => $jobUuid])
                ->start();
        }
    }
    
    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function displayInfoProcessStarted(OutputInterface $output)
    {
        $output->writeln('The process has started!');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return bool
     */
    protected function canContinueExecution(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('run') && !$input->getOption('run')) {
            $output->writeln('To start command, run it with option --run (-r)');
            return false;
        }
        
        return true;
    }
    
    /**
     * @param string|null $description
     * @return void
     */
    final protected function addOptionRun($description = null)
    {
        if (!$this->getDefinition()->hasOption('run')) {
            $this->addOption('run', 'r', InputOption::VALUE_NONE, $description ?: 'Run command');
        }
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string|null $jobUuid
     * @return Command
     */
    abstract protected function createQueueCommand(InputInterface $input, OutputInterface $output, $jobUuid);
}
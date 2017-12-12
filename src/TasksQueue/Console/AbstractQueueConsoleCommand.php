<?php

namespace FiiSoft\TasksQueue\Console;

use FiiSoft\Logger\Reader\LogsMonitor;
use FiiSoft\TasksQueue\Command;
use FiiSoft\TasksQueue\CommandQueue;
use FiiSoft\TasksQueue\QueueFactory;
use FiiSoft\TasksQueue\Worker\QueueWorker;
use FiiSoft\Tools\Console\AbstractCommand;
use FiiSoft\Tools\OutputWriter\Adapter\SymfonyConsoleOutputWriter;
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
    
    /** @var QueueWorker */
    private $queueWorker;
    
    /** @var QueueFactory */
    private $queueFactory;
    
    /** @var bool */
    protected $isInstant;
    
    /**
     * @param string $name name of command
     * @param QueueFactory $queueFactory
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct($name, QueueFactory $queueFactory)
    {
        parent::__construct($name);
    
        if (!$this->getDefinition()->hasOption('monitor')) {
            $this->addOption('monitor', 'm', InputOption::VALUE_NONE, 'Display progress of execution');
        }
    
        if (!$this->getDefinition()->hasOption('instant')) {
            $this->addOption('instant', 'i', InputOption::VALUE_NONE, 'Use in-memory queue to run command');
        }
        
        $this->queueFactory = $queueFactory;
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws \Exception
     * @return int
     */
    final protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        if (!$this->canContinueExecution($input, $output)) {
            return 10;
        }
    
        $this->isInstant = $input->hasOption('instant') && $input->getOption('instant');
        $this->setUpDependencies();
        
        $jobUuid = null;
        $monitoringEnabled = $input->hasOption('monitor') && $input->getOption('monitor') && !$this->isQuiet();
        
        if ($monitoringEnabled) {
            if ($input->isInteractive()) {
                $this->writelnV('Monitoring of execution is enabled');
                $jobUuid = Uuid::uuid4()->toString();
            } else {
                $output->writeln('Monitoring is unavailable when no-interaction mode is enabled!');
                return 100;
            }
        } else {
            $this->writelnV('Monitoring of execution is disabled');
        }
        
        $commands = $this->createQueueCommand($input, $output, $jobUuid);
        if (!$this->isCommandOrArrayOfCommands($commands)) {
            $output->writeln('Invalid value returned from method createQueueCommand');
            return 50;
        }
    
        if (!is_array($commands)) {
            $commands = [$commands];
        }
    
        foreach ($commands as $command) {
            $this->writelnV('Send command '.$command->getName().' to queue '.$this->commandQueue->queueName());
            $this->commandQueue->publishCommand($command);
        }
    
        $this->displayInfoProcessStarted($output);
    
        if ($monitoringEnabled) {
            if (!$this->isInstant) {
                $output->writeln('To turn off displaying logs use CTRL+C');
            }
        
            $this->logsMonitor
                ->setOutputWriter(new SymfonyConsoleOutputWriter($output))
                ->filterByContext(['jobUuid' => $jobUuid])
                ->start();
        }
    
        if ($this->isInstant) {
            $this->queueWorker->run(null, true);
            $output->writeln('Done');
        }
        
        return 0;
    }
    
    /**
     * @param mixed $command
     * @return bool
     */
    private function isCommandOrArrayOfCommands($command)
    {
        if (is_array($command)) {
            foreach ($command as $item) {
                if (! $item instanceof Command) {
                    return false;
                }
            }
            
            return true;
        }
        
        return $command instanceof Command;
    }
    
    /**
     * @return void
     */
    private function setUpDependencies()
    {
        if ($this->isInstant) {
            $this->queueFactory->useInstantImplementations(true);
            $this->queueWorker = $this->queueFactory->QueueWorker();
        } else {
            $this->queueFactory->useInstantImplementations(false);
            $this->queueWorker = null;
        }
        
        $this->commandQueue = $this->queueFactory->CommandQueue();
        $this->logsMonitor = $this->queueFactory->LogsMonitor();
    }
    
    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function displayInfoProcessStarted(OutputInterface $output)
    {
        if ($this->isInstant) {
            $output->writeln('The process has started. Do not interrupt it until its finished!');
        } else {
            $output->writeln('The process has started!');
        }
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string|null $jobUuid
     * @return Command[]|Command single command or array with commands
     */
    abstract protected function createQueueCommand(InputInterface $input, OutputInterface $output, $jobUuid);
}
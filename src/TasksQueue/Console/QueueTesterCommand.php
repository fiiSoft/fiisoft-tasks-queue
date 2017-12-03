<?php

namespace FiiSoft\TasksQueue\Console;

use ArrayIterator;
use FiiSoft\Logger\Reader\LogsMonitor;
use FiiSoft\Tools\Console\AbstractCommand;
use FiiSoft\Tools\OutputWriter\Adapter\SymfonyConsoleOutputWriter;
use FiiSoft\TasksQueue\Command\TestingCommand;
use FiiSoft\TasksQueue\CommandQueue;
use InfiniteIterator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueTesterCommand extends AbstractCommand
{
    /** @var CommandQueue */
    private $commandQueue;
    
    /** @var array */
    private $levels = [];
    
    /** @var integer */
    private $numOfLevels;
    
    /** @var LogsMonitor */
    private $logsMonitor;
    
    /**
     * @param CommandQueue $commandQueue
     * @param LogsMonitor $logsMonitor
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(CommandQueue $commandQueue, LogsMonitor $logsMonitor)
    {
        $this->levels = $logsMonitor->getLevels();
        $this->numOfLevels = count($this->levels);
        
        $this->commandQueue = $commandQueue;
        $this->logsMonitor = $logsMonitor;
        
        parent::__construct('queue:tester');
    }
    
    protected function configure()
    {
        $this->setDescription('Check if queue and workers are working properly by sending test-task to queue.')
            ->setHelp(
                'Allows to start testing tasks to check if queue and workers are workings properly.'.PHP_EOL
                .'Every subsequent task gets next level of logged messages send to queue logger.'.PHP_EOL
                .'Default number of tasks is '.$this->numOfLevels.', you can change it by option --tasks=100'.PHP_EOL
                .'Command class: '.get_class($this));
        
        $this->addOption(
            'tasks',
            't',
            InputOption::VALUE_REQUIRED,
            'How many tasks to start (default is '.$this->numOfLevels.')'
        );
    
        $this->addOption('monitor', 'm', InputOption::VALUE_NONE, 'Display progress of execution');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     * @return int
     */
    protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        $number = (int) $input->getOption('tasks');
        if ($number < 1) {
            $number = $this->numOfLevels;
        }
    
        $isMonitoringEnabled = $input->getOption('monitor') && !$this->isQuiet() && $input->isInteractive();
        if ($isMonitoringEnabled) {
            $this->writelnV('Monitoring of execution is enabled');
            $this->writeln('Waiting for logs from test. To exit press CTRL+C');
            
            $jobUuid = Uuid::uuid4()->toString();
            $this->logsMonitor
                ->filterByContext(['jobUuid' => $jobUuid])
                ->setOutputWriter(new SymfonyConsoleOutputWriter($output));
        } else {
            $this->writelnV('Monitoring of execution is disabled');
            $jobUuid = null;
        }
        
        $this->writelnVVV('Number of tasks to start: '.$number);
        $this->writelnVVV('Levels: '.implode(',', $this->levels));
        
        $levels = new InfiniteIterator(new ArrayIterator($this->levels));
        $levels->rewind();
    
        if ($isMonitoringEnabled) {
            //TODO is any better solution possible? Maybe something like register() or warmUp()?
            $this->logsMonitor->start(null, 1);
        }
        
        for ($i = 1; $i <= $number; ++$i) {
            $this->writelnV('Sending command '.$i.' with param level '.$levels->current());
            $this->commandQueue->publishCommand(new TestingCommand($i, $levels->current(), $jobUuid));
            $levels->next();
        }
        
        if ($isMonitoringEnabled) {
            $this->logsMonitor->start(null, 1);
        }
        
        return 0;
    }
}
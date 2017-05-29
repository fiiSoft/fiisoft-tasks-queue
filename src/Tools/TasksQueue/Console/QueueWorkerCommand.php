<?php

namespace FiiSoft\Tools\TasksQueue\Console;

use Exception;
use FiiSoft\Tools\Console\AbstractCommand;
use FiiSoft\Tools\TasksQueue\Worker\QueueWorker;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueWorkerCommand extends AbstractCommand
{
    const PROMPT = '::-> ';
    
    /** @var QueueWorker */
    private $worker;
    
    /** @var string */
    private $pidfilesPath;
    
    /** @var string */
    private $pidfilePrefix = 'queue_worker_';
    
    /**
     * @param QueueWorker $worker
     * @param string $pidfilesPath path to directory where pid file will be created
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(QueueWorker $worker, $pidfilesPath)
    {
        parent::__construct('queue:workers');
        $this->worker = $worker;
        $this->pidfilesPath = rtrim($pidfilesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    
    protected function configure()
    {
        $this->setDescription('Run and stop queue workers')
            ->setHelp('To start worker(s), run script with option --run (or -r).'.PHP_EOL.PHP_EOL
                .'This command allows to start and stop queue workers that executes tasks.'.PHP_EOL
                .'By default, only one process is started, but with option -w=X'.PHP_EOL
                .'number of started workers can be increased to X instances.'.PHP_EOL
                .'Also min level of logged messages can be set with option --level=level'.PHP_EOL
                .'Command class: '.get_class($this));
    
        $this->addOption('run', 'r', InputOption::VALUE_NONE, 'Run queue workers')
             ->addOption('stop', 's', InputOption::VALUE_OPTIONAL, 'Stop all (or some numbers) of queue workers', 'all')
             ->addOption('workers', 'w', InputOption::VALUE_REQUIRED, 'Number of workers to run')
             ->addOption('level', 'l', InputOption::VALUE_REQUIRED, 'Min level of messages to log');
    }
    
    protected function handleInput(InputInterface $input, OutputInterface $output)
    {
        $run = $input->getOption('run');
        $stop = $input->hasParameterOption(['-s', '--stop']);
        
        if (!$stop && !$run) {
            $output->writeln(
                'At least one option (ex. --run|-r or --stop|-s) is required to run this command.'.PHP_EOL
                .'See help for more information.'
            );
            return;
        }
    
        if ($stop && $run) {
            $output->writeln('I\'m sorry, I have no idea what to do... --run or --stop, no both!');
            return;
        }
        
        if ($run) {
            $this->handleRun($input, $output);
        } else {
            $this->handleStop($input->getOption('stop'));
        }
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function handleRun(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->createPidFile($this->pidfilesPath, $this->pidfilePrefix);
        
        $isWindows = false !== stripos(PHP_OS, 'WIN');
        $this->writelnVV(($isWindows ? 'Windows' : 'Non-windows').' OS detected');
        
        $canHandleSignals = function_exists('pcntl_signal');
        $this->writelnVV('PCNTL library '.($canHandleSignals ? 'is' : 'is not').' available');
        
        if (!$canHandleSignals && !$isWindows) {
            $output->writeln('It is strongly suggested to install PCNTL extension for better signals handling!');
        }
        
        $isInteractive = !$input->getOption('quiet') && !$input->getOption('no-interaction');
        $this->writelnVV('Interactive mode '.($isInteractive ? 'is' : 'is not').' enabled');
        
        if ($isInteractive && $isWindows) {
            $this->writelnV('Interactive mode is automatically disabled in Windows');
            $isInteractive = false;
        }
        
        if ($isInteractive) {
            $output->writeln('To stop worker(s) in gently way write "quit", "stop" or "end" and hit enter.');
            $output->writeln('To exit immediately press CTRL+C, but this method should be avoided.');
        } else {
            $output->writeln('Executing tasks from queue. To stop press CTRL+C.');
        }
    
        $minLevel = $input->getOption('level');
        if (!empty($minLevel)) {
            $this->worker->setMinimalLogLevel($minLevel);
            $this->writelnV('Option minLevel set to ' . $minLevel.' for QueueWorker');
        }
        
        $exitCode = 0;
        try {
            $this->runWorker($canHandleSignals, $isInteractive, $pidFile);
        } catch (Exception $e) {
            $output->writeln('Connection interrupted: ['.$e->getCode().'] '.$e->getMessage());
            $exitCode = 2;
        }
    
        if ($this->isPidFileExists($pidFile, true)) {
            unlink($pidFile);
        }
        
        $this->writelnVV('Command finished with exit code '.$exitCode);
        exit($exitCode);
    }
    
    /**
     * @param integer|null $stop
     * @return void
     */
    private function handleStop($stop = null)
    {
        $pattern = $this->pidfilesPath.$this->pidfilePrefix.'*.pid';
        $this->writelnVVV('Looking for pid files with pattern ' . $pattern);
    
        $this->writelnVV('Number of LOCAL workers to stop: ' . $stop);
        if ($stop === 'all') {
            $stop = 0;
        } else {
            $stop = (int) $stop;
        }
        
        $i = 0;
        foreach (glob($pattern, GLOB_NOSORT) as $item) {
            $this->writelnV('delete pid file '.$item);
            if (file_exists($item)) {
                unlink($item);
            }
    
            if (++$i === $stop) {
                break;
            }
        }
    }
    
    /**
     * @param bool $canHandleSignals
     * @param bool $isInteractive
     * @param string $pidFile
     * @throws RuntimeException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    private function runWorker($canHandleSignals, $isInteractive, $pidFile)
    {
        if ($isInteractive) {
            $this->writelnVV('Unblock read from STDIN in interactive mode');
            $this->output->write(self::PROMPT);
            stream_set_blocking(STDIN, false);
        }
        
        if ($canHandleSignals) {
            $continue = true;
            $this->registerSignalsHandlers($continue);
            
            declare(ticks=1) {
                while ($continue
                    && $this->isPidFileExists($pidFile)
                    && (!$isInteractive || $this->handleUserCommand())
                ) {
                    $this->writelnVVV('Run worker [block 1]');
                    $this->worker->runOnce(false);
                    pcntl_signal_dispatch();
                }
            }
        } elseif ($isInteractive) {
            while ($this->isPidFileExists($pidFile) && $this->handleUserCommand()) {
                $this->writelnVVV('Run worker [block 2]');
                $this->worker->runOnce(false);
            }
        } else {
            while ($this->isPidFileExists($pidFile)) {
                $this->writelnVVV('Run worker [block 3]');
                $this->worker->runOnce(false);
            }
        }
    }
    
    /**
     * @return bool if true then worker should continue to work
     */
    private function handleUserCommand()
    {
        //TODO this is not tested yet
        if (false === ($command = fgets(STDIN))) {
            return true;
        }
    
        $command = strtolower(trim($command));
        if ($command === 'end' || $command === 'stop' || $command === 'quit') {
            $this->output->writeln('Worker(s) stopped');
            return false;
        }
    
        if ($command !== '') {
            $this->output->writeln('Unrecognised command, use: end|stop|quit.');
            $this->output->write(self::PROMPT);
        }
        
        return true;
    }
    
    /**
     * @param bool $continue REFERENCE
     * @return void
     */
    private function registerSignalsHandlers(&$continue)
    {
        $this->writelnVV('Register signals handlers');
        //TODO https://en.wikipedia.org/wiki/Unix_signal
        $signals = [SIGINT, SIGTERM, SIGKILL, SIGSTOP];
    
        foreach ($signals as $signal) {
            pcntl_signal($signal, function ($signal) use (&$continue, $signals) {
                if (in_array($signal, $signals, true)) {
                    $continue = false;
                }
            });
        }
    }
}
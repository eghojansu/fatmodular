<?php

namespace App\command;

use fa;
use Base;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{
    protected $io;
    protected $input;
    protected $output;
    protected $startTime;

    protected function process($command, $cwd, $throwsError = false)
    {
        $process = new Process($command, $cwd);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            if ($throwsError) {
                throw new ProcessFailedException($process);
            } else {
                return false;
            }
        }

        return $process;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->startTime = microtime(true);

        return $this;
    }

    protected function notCompleted($message)
    {
        $diff = microtime(true)-$this->startTime;
        $timeElapsed = sprintf(' [Time elapsed: %s]', fa::microtime($diff));

        $this->io->error($message.$timeElapsed);
    }

    protected function reallyDone($message)
    {
        $diff = microtime(true)-$this->startTime;
        $timeElapsed = sprintf(' [Time elapsed: %s]', fa::microtime($diff));

        $this->io->block($message.$timeElapsed, 'DONE', 'fg=black;bg=yellow', ' ', true);
    }

    protected function info($info, $line = 1)
    {
        $this->output->write("<fg=yellow>{$info}...</>".str_repeat(PHP_EOL, $line));
    }

    protected function error($error, $line = 1)
    {
        $this->output->write("<fg=red>$error</>".str_repeat(PHP_EOL, $line));
    }

    protected function done($line = 1)
    {
        $this->output->write('<fg=green>done</>'.str_repeat(PHP_EOL, $line));
    }

    protected function base()
    {
        return Base::instance();
    }
}

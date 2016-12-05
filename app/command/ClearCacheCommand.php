<?php

namespace App\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ClearCacheCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('app:cache:clear')
            ->setDescription('Clear cache')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $base = $this->base();

        $fs = new Filesystem;
        $base->clear('CACHE');
        $tmp = $base->get('TEMP');
        $fs->remove($tmp);

        $this->reallyDone('Cache cleared');
    }
}

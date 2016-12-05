<?php

namespace Sample\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\command\CreateEntityCommand as CParent;

class CreateEntityCommand extends CParent
{
    protected $path = 'entity/';
    protected $namespace = 'Sample\\entity';

    public function configure()
    {
        $this->basepath = $this->base()->get('SROOT');
        $this
            ->setName('sample:entity:create')
            ->setDescription('Create entity')
            ->configureOther()
        ;
    }
}

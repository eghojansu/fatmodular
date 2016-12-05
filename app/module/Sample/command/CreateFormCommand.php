<?php

namespace Sample\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\command\CreateFormCommand as CParent;

class CreateFormCommand extends CParent
{
    protected $path = 'form/';
    protected $namespace = 'Sample\\form';

    public function configure()
    {
        $this->basepath = $this->base()->get('SROOT');
        $this
            ->setName('sample:form:create')
            ->setDescription('Create form')
            ->configureOther()
        ;
    }
}

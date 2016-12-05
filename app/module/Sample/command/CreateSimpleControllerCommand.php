<?php

namespace Sample\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\command\CreateSimpleControllerCommand as CParent;

class CreateSimpleControllerCommand extends CParent
{
    protected $path = 'controller/';
    protected $viewpath = 'view/master/';
    protected $ui_prefix = '@UIS';
    protected $namespace = 'Sample\\controller';
    protected $namespaceForm = 'Sample\\form';
    protected $namespaceEntity = 'Sample\\entity';

    public function configure()
    {
        $root = $this->base()->get('SROOT');
        $this->basepath = $root;
        $this->routeFile = $root.'config/routes.ini';
        $this->mapFile = $root.'config/maps.ini';

        $this
            ->setName('sample:controller:simple:create')
            ->setDescription('Create simple controller')
            ->configureOther()
        ;
    }
}

<?php

namespace App\command;

use fa;
use App\core\SQLTool;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Perform migration')
            ->addArgument('key', InputArgument::REQUIRED, 'Migration key')
            ->addArgument('scripts', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Script to performed')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);
        set_time_limit(600);

        $tool = new SQLTool(fa::db());
        $base = $this->base();
        $scripts = $input->getArgument('scripts');
        $keyname = 'MIGRATIONS.'.$input->getArgument('key');
        $scriptList = $base->get($keyname)?:[];
        foreach ($scriptList as $key=>$script) {
            if ('*' === $scripts[0] || in_array($key, $scripts))  {
                $tool->import($script);
            }
        }

        $this->reallyDone('Migration has been performed');
    }
}

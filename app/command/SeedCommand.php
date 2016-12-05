<?php

namespace App\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends AbstractCommand
{
    protected $sql;

    public function configure()
    {
        $this
            ->setName('app:db:seed')
            ->setDescription('Seed database content')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge database')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $this->reallyDone("Database seeding complete");
    }

    protected function call($execute = true, $callback = null)
    {
        if (!$execute || !$callback) {
            return $this;
        }

        call_user_func_array($callback, [$this]);

        return $this;
    }

    protected function truncate($table, $resetAutoIncrement = true)
    {
        if ($this->input->getOption('purge')) {
            $table = fa::db()->quotekey($table);
            $query = "set foreign_key_checks=0;delete from $table;set foreign_key_checks=1";
            if ($resetAutoIncrement) {
                $query .= ";alter table $table auto_increment=1";
            }

            fa::db()->exec($query);
        }

        return $this;
    }
}

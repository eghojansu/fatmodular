<?php

namespace App\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\entity\User;

class CreateUserCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('app:user:create')
            ->setDescription('Create user')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $map = new User;

        $username = $this->io->ask('Username : ', 'admin');
        $password = $this->io->ask('Password : ', 'admin');
        $roles = $this->io->ask('Roles (separate by comma) : ', 'admin');

        $search = ['username'=>$username];
        $map->loadBy($search);

        if ($map->valid()) {
            $this->notCompleted("username '$username' sudah ada");
            return;
        }

        $map->set('username', $username);
        $map->set('new_password', $password);
        $map->set('roles', str_replace(' ', '', $roles));
        $map->save();

        $this->reallyDone('User created');
    }
}

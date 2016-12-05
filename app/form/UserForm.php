<?php

namespace App\form;

use App\core\html\BootstrapHorizontalForm;

class UserForm extends BootstrapHorizontalForm
{
    protected $ignores = ['id','password','plain_password'];
    protected $labels = [
        'new_password'=>'Password',
    ];

    protected function init()
    {
        parent::init();

        $this->validation
            ->setLabels($this->labels)
            ->remove('active', ['required'])
            ->remove('roles')
            ->add('username', 'unique')
            ->add('username', 'minLength', [5,true])
            ->add('new_password', 'minLength', [5,$this->map->valid()])
            ->add('roles', 'simpleArray')
        ;
    }
}

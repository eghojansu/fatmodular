<?php

namespace App\form;

use App\core\html\BootstrapForm;

class LoginForm extends BootstrapForm
{
    public function init()
    {
        parent::init();

        $this->validation
            ->add('username', 'required')
            ->add('password', 'required')
        ;
    }
}

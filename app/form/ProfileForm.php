<?php

namespace App\form;

use Base;
use App\core\html\BootstrapHorizontalForm;

class ProfileForm extends BootstrapHorizontalForm
{
    protected $only = ['plain_password','new_password','username'];
    protected $labels = ['plain_password'=>'Password','new_password'=>'Password Baru'];

    public function init()
    {
        parent::init();

        $base = Base::instance();
        $this->validation
            ->setLabels($this->labels)
            ->add('plain_password', 'required')
            ->add('plain_password', function() use ($base) {
                return $base['user']->validatePassword($this->map->get('plain_password'));
            })
            ->add('username', 'minLength', [5,true])
            ->add('new_password', 'minLength', [5,true])
        ;
    }
}

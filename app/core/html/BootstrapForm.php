<?php

namespace App\core\html;

class BootstrapForm extends Form
{
    protected $controlAttrs = [
        'class'=>'form-control',
    ];

    public function rowOpen()
    {
        return '<div class="form-group">';
    }

    public function rowClose()
    {
        return '</div>';
    }
}

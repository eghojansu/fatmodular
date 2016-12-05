<?php

namespace Sample\form;

use Base;
use App\core\html\BootstrapHorizontalForm;
use App\core\SQLMapper;

class SampleForm extends BootstrapHorizontalForm
{
    protected $ignores = ['id',SQLMapper::TS_CREATE,SQLMapper::TS_UPDATE,SQLMapper::TS_DELETE];
    protected $labels = [];

    protected function init()
    {
        parent::init();

        $this->validation
            ->setLabels($this->labels)
        ;
    }
}

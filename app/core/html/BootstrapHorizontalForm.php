<?php

namespace App\core\html;

class BootstrapHorizontalForm extends BootstrapForm
{
    protected $attrs = [
        'class'=>'form-horizontal',
    ];
    protected $controlAttrs = [
        'class'=>'form-control',
    ];
    protected $labelAttrs = [
        'class'=>'control-label col-sm-2',
    ];

    public function row($type, $name, $label = null, array $attrs = [], array $labelAttrs = [], $override = false)
    {
        $attrs += ['override'=>false];
        $labelAttrs += ['control-class'=>'col-sm-10','override'=>false];
        $controlWidth = $labelAttrs['control-class'];
        $noLabel = false;
        if (in_array($type, ['checkbox','radio'])) {
            $noLabel = true;
            $controlWidth .= ' '.preg_replace_callback('/(\d+)$/', function($match) {
                return isset($match[1])?'offset-'.(12-$match[1]):$match[0];
            }, $controlWidth);
        }

        $aOverride = $attrs['override'];
        $lOverride = $labelAttrs['override'];
        unset($attrs['override'],$labelAttrs['override'],$labelAttrs['control-class']);

        $str = ($noLabel?'':$this->label($label?:$name, ['for'=>$name]+$labelAttrs, $lOverride))
             . '<div class="'.$controlWidth.'">'
             . $this->$type($name, $attrs, $aOverride)
             . $this->error($name)
             . '</div>';

        return $str;
    }
}

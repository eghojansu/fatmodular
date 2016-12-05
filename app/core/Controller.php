<?php

namespace App\core;

use Base;
use Template;
use Web;
use fa;

abstract class Controller
{
    const KEY_PREVIOUS = 'SESSION.previous';

    protected $homepage = '@homepage';
    protected $template = 'app.view.layout.base';
    protected $templateDisabled = false;

    protected function file($file, $delete = false, $fileAs = null, $mime = null, $kbps = 0, $force = true)
    {
        $base = Base::instance();
        $send = $file;
        if ($delete && $fileAs) {
            $send = dirname($file).'/'.$fileAs;
            rename($file, $send);
        }
        elseif ($fileAs) {
            $temp = $base->get('TEMP').'files/';
            if (!is_dir($temp)) {
                mkdir($temp, Base::MODE, TRUE);
            }
            copy($file, $temp.$fileAs);
            $send = $fileAs;
        }

        Web::instance()->send($send, $mime, $kbps, $force);

        if ($fileAs) {
            unlink($send);
        }
        $this->templateDisabled = true;
        $base->clear('VIEW');

        return $this;
    }

    protected function gotoRequestedPage()
    {
        $go = Base::instance()->get(static::KEY_PREVIOUS)?:$this->homepage;
        $this->redirect($go);
    }

    protected function gotoHomepage()
    {
        $this->redirect($this->homepage);
    }

    protected function refresh()
    {
        $this->redirect(null);
    }

    protected function redirect($route)
    {
        Base::instance()->reroute($route);
    }

    protected function json($data)
    {
        header('Content-type: application/json');

        echo is_string($data) ? $data : json_encode($data);

        $this->templateDisabled = true;
        Base::instance()->clear('VIEW');

        return $this;
    }

    protected function notFound()
    {
        $base = Base::instance();
        $base->error(404, $base['error.notfound']);
    }

    protected function forbidden()
    {
        $base = Base::instance();
        $base->error(405, $base['error.access']);
    }

    protected function render($view, $prefix = null)
    {
        $base = Base::instance();
        $base->set('VIEW', fa::view($view, false===$prefix?null:($prefix?:$base->get('UIROOT'))));

        return $this;
    }

    protected function template($value, $key = null)
    {
        $var = 'template'.($key?ucfirst($key):'');
        $this->$var = $value;

        return $this;
    }

    public function beforeroute($base, $params)
    {
        $this->templateDisabled = $base['AJAX'];
        $current = $base->get('ALIAS')?'@'.$base->get('ALIAS'):$base->get('URI');
        $previous = $base->get(static::KEY_PREVIOUS);
        if ($current != $previous) {
            $base->set(static::KEY_PREVIOUS, $current);
        }
    }

    public function afterroute($base, $params)
    {
        $base->clear(static::KEY_PREVIOUS);

        if ($this->templateDisabled) {
            $view = $base->get('VIEW');
            if ($view) {
                echo Template::instance()->render($view);
            }

            return;
        }

        $template = fa::view($this->template);
        echo Template::instance()->render($template);
    }
}

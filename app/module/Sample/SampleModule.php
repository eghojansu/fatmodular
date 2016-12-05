<?php

namespace Sample;

use App\core\AbstractModule;
use Base;

class SampleModule extends AbstractModule
{
    public function getCommands()
    {
        return $this->findCommands(__DIR__.'/command');
    }

    public function init()
    {
        $base = Base::instance();

        $sroot = $base->fixslashes(__DIR__).'/';
        $config = [
            'SROOT'=>$base->fixslashes(__DIR__).'/',
            'UIS'=>str_replace('/', '.', str_replace($base['ROOT'], '', $sroot)).'view.',
        ];
        $base->mset($config);
        $base->merge('MIGRATIONS', [
            'sup'=>$this->findFiles(__DIR__.'/migration/up', '*.sql'),
            'sdown'=>$this->findFiles(__DIR__.'/migration/down', '*.sql'),
        ], true);
        $base->config(__DIR__.'/config/routes.ini');
        $base->config(__DIR__.'/config/maps.ini');
    }
}

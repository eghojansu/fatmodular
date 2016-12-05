<?php

namespace App\core;

use Base;

class CoreModule extends AbstractModule
{
    public function getCommands()
    {
        return $this->findCommands(__DIR__.'/../command');
    }

    public function init()
    {
        $base = Base::instance();

        $config = [
            'UIROOT'=>'app.view.',
        ];
        $base->mset($config);
        $base->merge('MIGRATIONS', [
            'clean'=>$this->findFiles(__DIR__.'/../migration/clean', '*.sql'),
            'down'=>$this->findFiles(__DIR__.'/../migration/down', '*.sql'),
            'up'=>$this->findFiles(__DIR__.'/../migration/up', '*.sql'),
        ], true);
        $base->config(__DIR__.'/../config/app.ini');
        $base->config(__DIR__.'/../config/maps.ini');
        $base->config(__DIR__.'/../config/redirects.ini');
        $base->config(__DIR__.'/../config/routes.ini');
    }
}

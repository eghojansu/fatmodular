<?php

namespace App;

use fa;
use filter;
use ext;
use Base;
use Template;
use Symfony\Component\Console\Application;

class Kernel
{
    const ENV_PROD = 'prod';
    const ENV_DEV  = 'dev';
    const ENV_TEST = 'test';

    protected $env = self::ENV_PROD;

    protected function modules()
    {
        $modules = [
            new \App\core\CoreModule,
            new \Sample\SampleModule,
        ];

        return $modules;
    }

    public function setEnv($env)
    {
        $this->env = $env;
        if (in_array($env, [static::ENV_DEV, static::ENV_TEST])) {
            Base::instance()->set('DEBUG', 3);
        }

        return $this;
    }

    public function console($init = true)
    {
        if ($init) {
            $this->init();
        }

        $app = new Application('Fatmodular Console Tool');
        foreach ($this->modules() as $module) {
            foreach ($module->getCommands() as $command) {
                $app->add(new $command);
            }
        }
        // fix argv
        if (2 === $_SERVER['argc'] && '/' === $_SERVER['argv'][1]) {
            $_SERVER['argc'] = 1;
            array_pop($_SERVER['argv']);
        }

        return $app;
    }

    public function web($init = true)
    {
        if ($init) {
            $this->init();
        }

        $template = Template::instance();
        $filters = [
            'path'=>'fa::path',
            'view'=>'fa::view',
        ];
        foreach ($filters as $alias => $filter) {
            $alias = is_numeric($alias)?$filter:$alias;
            $template->filter($alias, $filter);
        }
        foreach (filter::getFilters() as $alias=>$filter) {
            $template->filter($alias, 'filter::'.$filter);
        }
        foreach (ext::getExtensions() as $alias=>$ext) {
            $template->extend($alias, 'ext::'.$ext);
        }

        // initiates user
        Base::instance()->set('user', new \App\core\User(new \App\entity\User));

        return Base::instance();
    }

    public function init()
    {
        foreach ($this->modules() as $module) {
            $module->init();
        }

        return $this;
    }

    public function __construct()
    {
        $base = Base::instance();

        $root = $base->fixslashes(dirname(__DIR__)).'/';
        $config = [
            'LOGS'=>$root.'var/logs/',
            'TEMP'=>$root.'var/tmp/',
            'UPLOADS'=>$root.'var/uploads/',
            'CACHE'=>"folder={$root}var/cache/",
            // 'CACHE'=>true,
            'LOCALES'=>$root.'app/dict/',
            'LANGUAGE'=>'id',
            'UI'=>$root,
            'TZ'=>'Asia/Jakarta',
            'APP'=>$root.'app/',
            'AUTOLOAD'=>$root.'app/module/',
            'ROOT'=>$root,
            'VIEW'=>null,
            'PAGE'=>'page',
            'LIMIT'=>'record',
            'LIMIT_LIST'=>[5,10],
            'SORT'=>'sort',
            'MIGRATIONS'=>[],
        ];
        $base->mset($config);
    }
}

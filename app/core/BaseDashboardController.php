<?php

namespace App\core;

abstract class BaseDashboardController extends Controller
{
    protected $template = 'app.view.layout.dashboard';

    public function beforeroute($base, $params)
    {
        parent::beforeroute($base, $params);

        $base['user']->denyUnlessGranted('user');
    }
}

<?php

namespace App\controller;

use App\core\Controller;
use App\core\html\Menu;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $this->render('default.index');
    }

    public function dashboardAction($base)
    {
        $base['user']->denyUnlessGranted('user');

        $this->template('app.view.layout.dashboard')->render('default.dashboard');
    }
}

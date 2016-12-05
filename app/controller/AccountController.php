<?php

namespace App\controller;

use App\core\Controller;
use App\form\LoginForm;
use App\form\ProfileForm;

class AccountController extends Controller
{
    public function loginAction($base)
    {
        $base['user']->denyUnlessGranted('anon');

        $form = new LoginForm;
        if ($form->valid() && $base['user']->loginWithTokenPassword($form->username, $form->password)) {
            $base['user']->addMessage('success', $base['login.success']);
            $this->gotoRequestedPage();
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('account.login');
    }

    public function profileAction($base)
    {
        $base['user']->denyUnlessGranted('user');

        $form = new ProfileForm($base['user']->map);
        if ($form->valid()) {
            $base['user']->map->save();

            $base['user']->addMessage('success', $base['profile.updated']);
            $this->refresh();
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->template('app.view.layout.dashboard')->render('account.profile');
    }

    public function logoutAction($base)
    {
        $base['user']->denyUnlessGranted('user');

        $base['user']->logout();
    }
}

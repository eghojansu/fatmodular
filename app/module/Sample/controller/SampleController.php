<?php

namespace Sample\controller;

use App\core\BaseDashboardController;
use App\core\html\Pagination;
use Sample\entity\Sample;
use Sample\form\SampleForm;
use App\form\SearchForm;
use nav;

class SampleController extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $data = $this->model()->listing();
        $base->mset([
            'data'=>$data,
            'search'=>new SearchForm,
            'pagination'=>new Pagination($data),
        ]);

        $this->render('master.sample.index', $base['UIS']);
    }

    public function createAction($base, $args)
    {
        $map = $this->model();
        $form = new SampleForm($map);
        if ($form->valid()) {
            $map->updateTimestamp()->insert();
            $base['user']->addMessage('success', $base['crud.created']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.sample.create', $base['UIS']);
    }

    public function updateAction($base, $args)
    {
        $map = $this->model($args['id'], true);
        $form = new SampleForm($map);
        if ($form->valid()) {
            $map->updateTimestamp()->update();
            $base['user']->addMessage('success', $base['crud.updated']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.sample.update', $base['UIS']);
    }

    public function deleteAction($base, $args)
    {
        $map = $this->model($args['id'], true);
        $map->erase();
        $base['user']->addMessage('warning', $base['crud.deleted']);
        $this->redirect('@'.$base['index']);
    }

    private function model($id = false, $required = false)
    {
        $map = new Sample;

        if ($id) {
            $map->loadByKey($id);

            if ($required && $map->dry()) {
                $this->notFound();
            }
        }

        return $map;
    }

    public function beforeroute($base, $args)
    {
        parent::beforeroute($base, $args);
        $base['user']->denyUnlessGranted('admin');
        $base->mset([
            'index'=>'sample_index',
            'update'=>'sample_update',
            'delete'=>'sample_delete',
            'create'=>'sample_create',
            ]);
        nav::active($base['index']);
    }
}

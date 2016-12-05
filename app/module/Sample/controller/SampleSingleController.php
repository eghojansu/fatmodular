<?php

namespace Sample\controller;

use App\core\BaseDashboardController;
use Sample\entity\Sample;
use Sample\form\SampleForm;
use nav;
use fa;

class SampleSingleController extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $map = $this->model();
        $form = new SampleForm($map);
        $form
            ->setAction(fa::path('single_map'))
            ->addAttr('data-form', 'desktop')
            ->addDefaultControlAttr('disabled')
        ;

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.sample.single', $base['UIS']);
    }

    public function searchAction($base, $args)
    {
        $map = $this->model();
        $result = $map->search($base['GET.q.term'], $base['GET.records']);

        $this->json($result);
    }

    public function get($base, $args)
    {
        $dir = $base->get('GET.dir')?:'first';
        $id = 'empty'===$args['id']?null:$args['id'];

        $this->json($this->model()->getItem($id, $dir));
    }

    public function post($base, $args)
    {
        $map = $this->model();
        $form = new SampleForm($map);
        if ($form->assignFromRequest()->validation->validate()->valid()) {
            $map->updateTimestamp()->insert();
            $result = [
                'success'=>true,
                'message'=>$base->get('crud.saved'),
                'update'=>['id'=>$map->id],
                'prev'=>$map->prevItem(),
            ];
        }
        else {
            $result = [
                'success'=>false,
                'message'=>$base->get('crud.not_saved'),
                'error'=>$form->validation->getErrors(),
            ];
        }

        $this->json($result);
    }

    public function put($base, $args)
    {
        $map = $this->model($args['id'], true);
        $form = new SampleForm($map);
        if ($form->assignFromRequest()->validation->validate()->valid()) {
            $map->updateTimestamp()->update();
            $result = [
                'success'=>true,
                'message'=>$base->get('crud.saved'),
                'update'=>['id'=>$map->id],
                'prev'=>$map->prevItem(),
            ];
        }
        else {
            $result = [
                'success'=>false,
                'message'=>$base->get('crud.not_saved'),
                'error'=>$form->validation->getErrors(),
            ];
        }

        $this->json($result);
    }

    public function delete($base, $args)
    {
        $map = $this->model($args['id'], true);
        $map->erase();
        $result = [
            'success'=>true,
            'message'=>$base->get('crud.deleted'),
        ];

        $this->json($result);
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
            'index'=>'simple_index',
            'search'=>'simple_search',
            'sample'=>'single_map',
            ]);
        nav::active($base['index']);
    }
}

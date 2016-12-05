<?php

namespace App\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSimpleControllerCommand extends AbstractCommand
{
    protected $basepath;
    protected $path = 'app/controller/';
    protected $viewpath = 'app/view/master/';
    protected $namespace = 'App\\controller';
    protected $namespaceEntity = 'App\\entity';
    protected $namespaceForm = 'App\\form';
    protected $ui_prefix = '@UIROOT';
    protected $routeFile;
    protected $mapFile;
    protected $data = [];

    public function configure()
    {
        $root = $this->base()->get('ROOT');
        $this->basepath = $root;
        $this->routeFile = $root.'app/config/routes.ini';
        $this->mapFile = $root.'app/config/maps.ini';

        $this
            ->setName('app:controller:simple:create')
            ->setDescription('Create simple controller')
            ->configureOther()
        ;
    }

    protected function configureOther()
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'entity name')
            ->addArgument('controller', InputArgument::OPTIONAL, 'controller name')
            ->addOption('no-route', 'u', InputOption::VALUE_NONE, 'Do not create route')
            ->addOption('no-view', 'w', InputOption::VALUE_NONE, 'Do not create view')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete if exists')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $this->prepareData();

        $base = $this->base();
        $path = $this->basepath.$this->path;
        $file = $path.$this->data['{controller}'].'.php';

        if ($input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if (!is_dir($path)) {
            @mkdir($path, $base::MODE, true);
        }

        $hints = array_keys($this->data);
        $replace = array_values($this->data);
        if (!file_exists($file)) {
            file_put_contents($file, str_replace($hints, $replace, $this->template()));
        }

        if (!$input->getOption('no-route')) {
            $routeFile = $this->routeFile;
            $mapFile = $this->mapFile;
            if (!is_dir(dirname($routeFile))) {
                @mkdir(dirname($routeFile), $base::MODE, true);
            }
            $routeContent = @file_get_contents($routeFile)?:'';
            if (false === strpos($routeContent, '@'.$this->data['{route}'].'_index')) {
                if (!$routeContent) {
                    file_put_contents($routeFile, '[routes]'.PHP_EOL);
                }
                file_put_contents($routeFile, str_replace($hints, $replace, $this->templateRoute()), FILE_APPEND);
            }
            $mapContent = @file_get_contents($mapFile)?:'';
            if (false === strpos($mapContent, '@'.$this->data['{map}'].'_index')) {
                if (!$mapContent) {
                    file_put_contents($mapFile, '[maps]'.PHP_EOL);
                }
                file_put_contents($mapFile, str_replace($hints, $replace, $this->templateMap()), FILE_APPEND);
            }
        }

        if (!$input->getOption('no-view')) {
            $viewpath = $this->basepath.$this->viewpath.$this->data['{route}'].'/';
            if (!is_dir($viewpath)) {
                @mkdir($viewpath, $base::MODE, true);
            }
            $ext = '.html';
            $viewdata = $this->templateView();
            foreach ($viewdata as $view => $template) {
                $view = $viewpath.$view.$ext;

                if ($input->getOption('del') && file_exists($view)) {
                    unlink($view);
                }

                if (!file_exists($view)) {
                    file_put_contents($view, str_replace($hints, $replace, $template));
                }
            }
        }

        $this->reallyDone('Controller created');
    }

    protected function prepareData()
    {
        $base = $this->base();
        $entityName = $this->input->getArgument('entity');
        $controllerName = ($this->input->getArgument('controller')?:$entityName).'Controller';

        $route = $base->snakecase(lcfirst($entityName));
        $title = ucwords(str_replace('_', ' ', $route));
        $url = str_replace('_', '-', $route);

        $this->data['{entity}'] = $entityName;
        $this->data['{controller}'] = $controllerName;
        $this->data['{ui_prefix}'] = $this->ui_prefix;
        $this->data['{namespace}'] = $this->namespace;
        $this->data['{namespaceEntity}'] = $this->namespaceEntity;
        $this->data['{namespaceForm}'] = $this->namespaceForm;
        $this->data['{route}'] = $route;
        $this->data['{map}'] = $route;
        $this->data['{url}'] = $url;
        $this->data['{title}'] = $title;

        $entityClass = $this->namespaceEntity.'\\'.$entityName;
        if (class_exists($entityClass)) {
            $entity = new $entityClass;
            $fields = $entity->fields();

            $this->data['{fields}'] = '';
            $this->data['{headers}'] = '';
            $this->data['{form}'] = '';
            foreach ($fields as $field) {
                if (in_array($field, ['id',$entity::TS_CREATE,$entity::TS_UPDATE,$entity::TS_DELETE])) {
                    continue;
                }
                $header = ucwords(str_replace('_', ' ', $field));
                $this->data['{fields}'] .= '<td>{{ @item.'.$field.' }}</td>'.PHP_EOL.str_repeat("\t", 5);
                $this->data['{headers}'] .= '<th>'.$header.'</th>'.PHP_EOL.str_repeat("\t", 4);
                $this->data['{form}'] .= <<<FORM
    {{ @form->rowOpen() }}
        {{ @form->row('text', '{$field}', null, [], ['control-class'=>'col-sm-3']) }}
    {{ @form->rowClose() }}

FORM;
            }
        }
    }

    protected function template()
    {
        $content = <<<'CONTENT'
<?php

namespace {namespace};

use App\core\BaseDashboardController;
use {namespaceEntity}\{entity};
use {namespaceForm}\{entity}Form;
use nav;
use fa;

class {controller} extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $map = $this->model();
        $form = new {entity}Form($map);
        $form
            ->setAction(fa::path($base['map']))
            ->addAttr('data-form', 'desktop')
            ->addDefaultControlAttr('disabled')
        ;

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.{route}.single', {ui_prefix});
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
        $form = new {entity}Form($map);
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
        $form = new {entity}Form($map);
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
        $map = new {entity};

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
            'index'=>'{route}_index',
            'search'=>'{route}_search',
            'map'=>'{map}_map',
            ]);
        nav::active($base['index']);
    }
}


CONTENT;

        return $content;
    }

    protected function templateRoute()
    {
        $routeData = <<<'ROUTE'

; {entity}
GET @{route}_index: /dashboard/master/{url} [sync] = {namespace}\{controller}->indexAction
GET @{route}_search: /dashboard/master/{url}/search [ajax] = {namespace}\{controller}->searchAction

ROUTE;

        return $routeData;
    }

    protected function templateMap()
    {
        $routeData = <<<'ROUTE'

; {entity}
@{map}_map: /dashboard/master/{url}/@id [ajax] = {namespace}\{controller}

ROUTE;

        return $routeData;
    }

    protected function templateView()
    {
        $content = [];

        $content['single'] = <<<'CONTENT'
<div class="panel panel-primary panel-main">
    <div class="panel-heading clearfix">
        <div class="pull-right">
            <form class="form-inline" data-form-connect="[data-form=desktop]">
                <div class="form-group">
                    <select data-utilize="select2" class="form-control" data-url="{{ @search|path }}" data-placeholder="search keyword"></select>
                </div>
            </form>
        </div>

        <h1>Data {title}</h1>
    </div>
    <div class="panel-body">

        {{ @form->open() }}
            {{ @form->hidden('id') }}
            {form}
            {{ @form->rowOpen() }}
                <div class="col-sm-10 col-sm-offset-2">
                    <button type="submit" class="btn btn-info" disabled><i class="fa fa-check"></i> {{ @crud.save }}</button>
                    <button type="reset" class="btn btn-default" disabled><i class="fa fa-ban"></i> {{ @crud.reset }}</button>
                    <a href="#new" class="btn btn-primary"><i class="fa fa-pencil"></i> {{ @crud.create }}</a>
                    <a href="#delete" class="btn btn-danger disabled"><i class="fa fa-trash"></i> {{ @crud.delete }}</a>

                    <div class="btn btn-group">
                        <a href="#prev" class="btn btn-default disabled"><i class="fa fa-chevron-left"></i> {{ @crud.prev }}</a>
                        <a href="#next" class="btn btn-default disabled">{{ @crud.next }} <i class="fa fa-chevron-right"></i></a>
                    </div>
                </div>
            {{ @form->rowClose() }}
        {{ @form->close() }}

    </div>
</div>

CONTENT;

        return $content;
    }
}

<?php

namespace App\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CreateControllerCommand extends AbstractCommand
{
    protected $basepath;
    protected $path = 'app/controller/';
    protected $viewpath = 'app/view/master/';
    protected $namespace = 'App\\controller';
    protected $namespaceEntity = 'App\\entity';
    protected $namespaceForm = 'App\\form';
    protected $ui_prefix = '@UIROOT';
    protected $routeFile;
    protected $data = [];

    public function configure()
    {
        $root = $this->base()->get('ROOT');
        $this->basepath = $root;
        $this->routeFile = $root.'app/config/routes.ini';

        $this
            ->setName('app:controller:create')
            ->setDescription('Create controller')
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
        $this->data['{url}'] = $url;
        $this->data['{title}'] = $title;
        $this->data['{header_count}'] = 1;

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
                $this->data['{header_count}']++;
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
use App\core\html\Pagination;
use {namespaceEntity}\{entity};
use {namespaceForm}\{entity}Form;
use App\form\SearchForm;
use nav;

class {controller} extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $data = $this->model()->listing();
        $base->mset([
            'data'=>$data,
            'search'=>new SearchForm,
            'pagination'=>new Pagination($data),
        ]);

        $this->render('master.{route}.index', $base['UIROOT']);
    }

    public function createAction($base, $args)
    {
        $map = $this->model();
        $form = new {entity}Form($map);
        if ($form->valid()) {
            $map->updateTimestamp()->insert();
            $base['user']->addMessage('success', $base['crud.created']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.{route}.create', $base['UIROOT']);
    }

    public function updateAction($base, $args)
    {
        $map = $this->model($args['id'], true);
        $form = new {entity}Form($map);
        if ($form->valid()) {
            $map->updateTimestamp()->update();
            $base['user']->addMessage('success', $base['crud.updated']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.{route}.update', $base['UIROOT']);
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
            'update'=>'{route}_update',
            'delete'=>'{route}_delete',
            'create'=>'{route}_create',
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
GET|POST @{route}_create: /dashboard/master/{url}/create [sync] = {namespace}\{controller}->createAction
GET|POST @{route}_update: /dashboard/master/{url}/@id/update [sync] = {namespace}\{controller}->updateAction
GET @{route}_delete: /dashboard/master/{url}/@id/delete [sync] = {namespace}\{controller}->deleteAction

ROUTE;

        return $routeData;
    }

    protected function templateView()
    {
        $content = [];

        $content['create'] = <<<'CONTENT'
<div class="panel panel-primary panel-main">
    <div class="panel-heading">
        <h1>Tambah Data {title}</h1>
    </div>
    <div class="panel-body">
        <include href="{{ 'master.{route}.form',{ui_prefix}|view }}" />
    </div>
</div>
CONTENT;

        $content['update'] = <<<'CONTENT'
<div class="panel panel-primary panel-main">
    <div class="panel-heading">
        <h1>Edit Data {title}</h1>
    </div>
    <div class="panel-body">
        <include href="{{ 'master.{route}.form',{ui_prefix}|view }}" />
    </div>
</div>
CONTENT;

        $content['index'] = <<<'CONTENT'
<div class="panel panel-primary panel-main">
    <div class="panel-heading">
        <h1>Data {title}</h1>
    </div>
    <div class="panel-body">
        <div class="crud-panel">
            <div class="btn btn-group pull-right">
                <a href="{{ '{route}_create'|path }}" class="btn btn-primary"><i class="fa fa-pencil"></i> {{ @crud.create }}</a>
            </div>
        </div>

        <include href="{{ 'partial.crud_control',@UIROOT|view }}" />

        <set colspan="{header_count}" />
        <table class="table table-condensed table-bordered table-hover table-striped">
            <thead>
                <tr>
                    {headers}
                    <th>tools</th>
                </tr>
            </thead>
            <tbody>
                <repeat group="{{ @data.subset }}" value="{{ @item }}">
                    <tr>
                        {fields}
                        <td>
                            <set p="{{ ['id'=>@item.id] }}" />
                            <a href="{{ '{route}_update', @p | path }}" class="btn btn-xs btn-info"><i class="fa fa-edit"></i> {{ @crud.update }}</a>
                            <a data-confirm="delete" href="{{ '{route}_delete', @p | path }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> {{ @crud.delete }}</a>
                        </td>
                    </tr>
                </repeat>
                <check if="{{ !@data.subset }}">
                    <tr>
                        <td colspan="{{ @colspan }}"><em>{{ @crud.empty }}</em></td>
                    </tr>
                </check>
            </tbody>
        </table>

        <include href="{{ 'partial.crud_info',@UIROOT|view }}" />
    </div>
</div>
CONTENT;

        $content['form'] = <<<'CONTENT'
{{ @form->open() }}
    {form}
    {{ @form->rowOpen() }}
        <div class="col-sm-10 col-sm-offset-2">
            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> {{ @crud.save }}</button>
            <a href="{{ '{route}_index'|path }}" class="btn btn-default"><i class="fa fa-ban"></i> {{ @crud.cancel }}</a>
        </div>
    {{ @form->rowClose() }}
{{ @form->close() }}
CONTENT;

        return $content;
    }
}

<?php

namespace App\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CreateFormCommand extends AbstractCommand
{
    protected $basepath;
    protected $path = 'app/form/';
    protected $namespace = 'App\\form';
    protected $data = [];

    public function configure()
    {
        $this->basepath = $this->base()->get('ROOT');
        $this
            ->setName('app:form:create')
            ->setDescription('Create form')
            ->configureOther()
        ;
    }

    protected function configureOther()
    {
        $this
            ->addArgument('form', InputArgument::REQUIRED, 'form name')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete if exists')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $base = $this->base();

        $this->prepareData();

        $path = $this->basepath.$this->path;
        $file = $path.$this->data['{form}'].'.php';

        if ($this->input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if (!is_dir($path)) {
            @mkdir($path, $base::MODE, true);
        }

        if (!file_exists($file)) {
            file_put_contents($file, str_replace(array_keys($this->data), array_values($this->data), $this->template()));
        }

        $this->reallyDone('Form created');
    }

    protected function prepareData()
    {
        $base = $this->base();

        $formName = $this->input->getArgument('form').'Form';

        $this->data['{namespace}'] = $this->namespace;
        $this->data['{form}'] = $formName;
    }

    protected function template()
    {
        $content = <<<'CONTENT'
<?php

namespace {namespace};

use Base;
use App\core\html\BootstrapHorizontalForm;
use App\core\SQLMapper;

class {form} extends BootstrapHorizontalForm
{
    protected $ignores = ['id',SQLMapper::TS_CREATE,SQLMapper::TS_UPDATE,SQLMapper::TS_DELETE];
    protected $labels = [];

    protected function init()
    {
        parent::init();

        $base = Base::instance();
        $fields = $this->map->fields(false);
        foreach ($fields as $field) {
            $this->labels[$field] = $base->get('all.'.$field);
        }

        $this->validation
            ->setLabels($this->labels)
            ->add('code', 'unique')
            ->add('name', 'unique')
            ->remove('default', ['required'])
        ;
    }
}

CONTENT;

        return $content;
    }
}

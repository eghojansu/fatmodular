<?php

namespace App\command;

use fa;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CreateEntityCommand extends AbstractCommand
{
    protected $basepath;
    protected $path = 'app/entity/';
    protected $namespace = 'App\\entity';
    protected $data = [];

    public function configure()
    {
        $this->basepath = $this->base()->get('ROOT');
        $this
            ->setName('app:entity:create')
            ->setDescription('Create entity')
            ->configureOther()
        ;
    }

    protected function configureOther()
    {
        $this
            ->addArgument('tables', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'tables name')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete if exists')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        foreach ($input->getArgument('tables') as $table) {
            $this->createEntity($table);
        }

        $this->reallyDone('Entities created');
    }

    private function createEntity($table)
    {
        $base = $this->base();

        $this->prepareData();
        $entityName = ucfirst($base->camelcase($table));

        $data = $this->data;
        $data['{entity}'] = $entityName;

        $path = $this->basepath.$this->path;
        $file = $path.$entityName.'.php';

        if ($this->input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if (!is_dir($path)) {
            @mkdir($path, $base::MODE, true);
        }

        if (!file_exists($file)) {
            file_put_contents($file, str_replace(array_keys($data), array_values($data), $this->template()));
        }
    }

    protected function prepareData()
    {
        $base = $this->base();

        $this->data['{namespace}'] = $this->namespace;
    }

    protected function template()
    {
        $content = <<<'CONTENT'
<?php

namespace {namespace};

use Base;
use App\core\SQLMapper;
use App\core\SimpleCursor;

class {entity} extends SQLMapper
{
    use SimpleCursor;

    public function __construct()
    {
        parent::__construct();
        $this->aftersave(function() {
            if ($this->get('default')) {
                $this->db->exec("update {$this->table} set `default` = 0 where id <> ?", $this->get('id'));
            }
        });
    }

    public function listing()
    {
        $filter = [self::TS_DELETE.' is null'];
        if (isset($_GET['keyword']) && $_GET['keyword']) {
            $filter[0] .= ' and (code like :keyword or name like :keyword)';
            $filter[':keyword'] = '%'.$_GET['keyword'].'%';
        }
        $option = ['order'=>'id'];

        return $this->apaginate($filter, $option);
    }

    public function search($keyword, $records)
    {
        $result = [];
        $filter = ['name like :keyword', ':keyword'=>'%'.$keyword.'%'];
        $option = ['limit'=>$records];

        foreach ($this->find($filter, $option) as $item) {
            $result[] = ['id'=>$item['id'],'text'=>$item['name']];
        }

        return ['items'=>$result];
    }
}

CONTENT;

        return $content;
    }
}

<?php

namespace Sample\entity;

use App\core\SQLMapper;
use App\core\SimpleCursor;
use Base;

class Sample extends SQLMapper
{
    use SimpleCursor;

    public function listing()
    {
        $filter = [self::TS_DELETE.' is null'];
        if (isset($_GET['keyword']) && $_GET['keyword']) {
            $filter[0] .= ' and (name like :keyword)';
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

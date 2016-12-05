<?php

namespace App\core;

trait SimpleCursor
{
    public function getItem($id, $dir)
    {
        $limit1 = ['limit'=>1,'order'=>'id asc'];
        $limit2 = ['limit'=>2,'order'=>'id asc'];
        $ignore = [self::TS_CREATE,self::TS_UPDATE,self::TS_DELETE];
        switch ($dir) {
            case 'first':
                $this->load(null, $limit2);
                $item = $this->cast();
                $next = $this->next()?$this->valid():false;
                $prev = false;
                $this->reset();
                break;
            case 'next':
                $n = $this->nextItem($id);
                $item = $n->cast();
                $next = $n->nextItem($item['id'])->valid();
                $prev = $n->prevItem($item['id'])->valid();
                break;
            case 'prev':
                $n = $this->prevItem($id);
                $item = $n->cast();
                $next = $n->nextItem($item['id'])->valid();
                $prev = $n->prevItem($item['id'])->valid();
                break;
            case 'current':
                $n = $this->loadByKey($id);
                $item = $n->cast();
                $next = $n->nextItem($item['id'])->valid();
                $prev = $n->prevItem($item['id'])->valid();
                break;
            default:
                $item = [];
                $next = false;
                $prev = false;
                break;
        }

        foreach ($ignore as $field) {
            unset($item[$field]);
        }

        return [
            'item'=>$item,
            'next'=>$next,
            'prev'=>$prev,
        ];
    }

    public function nextItem($id = null)
    {
        $clone = clone $this;
        $filter = ['id > :id', ':id'=>$id?:$this->id];
        $clone->load($filter, ['limit'=>1,'order'=>'id asc']);

        return $clone;
    }

    public function prevItem($id = null)
    {
        $clone = clone $this;
        $filter = ['id < :id', ':id'=>$id?:$this->id];
        $clone->load($filter, ['limit'=>1,'order'=>'id desc']);

        return $clone;
    }
}

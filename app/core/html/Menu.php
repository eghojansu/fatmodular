<?php

namespace App\core\html;

use fa;

class Menu extends HTML
{
    protected $parent;
    protected $menu;
    protected $menuKey;
    protected $show;
    protected $option;
    protected $activeRoute;
    protected $activeClass = 'active';
    protected $childActive = false;
    protected $childs = [];
    protected $default = [];

    public function __construct($menu = null, array $option = null, Menu $parent = null, $show = true)
    {
        $this->show = $show;
        $this->menu = $menu;
        $this->parent = $parent;
        $this->menuKey = implode('.', array_filter([$this->parent?$this->parent->getKey():'',$this->menu]));
        $this->option = ($option?:[])+[
            'route'=>null,
            'args'=>[],
            'url'=>null,
            'divider'=>false,
            'prefix'=>'',
            'suffix'=>'',
            'identifier'=>false,
            'link'=>[],
            'attr'=>[],
            'list'=>[],
        ];
    }

    public function add($menu, array $option = null, $show = true)
    {
        $child = new Menu($menu, $option, $this, $show);
        $child
            ->setDefault($this->default)
            ->setActiveClass($this->activeClass)
            ->setActiveRoute($this->activeRoute)
        ;
        $this->childs[$menu] =& $child;

        return $child;
    }

    public function &get($menu)
    {
        $null = null;
        $var =& $this->childs;
        $parts = explode('.', $menu);
        foreach ($parts as $part) {
            if (!is_array($var))
                $var = $var instanceof Menu ? $var->getChild() : [];
            if (array_key_exists($part,$var))
                $var=&$var[$part];
            else {
                $var=&$null;
                break;
            }
        }

        return $var;
    }

    public function addDivider(array $option = [], $show = true)
    {
        $option += [
            'divider'=>true,
        ];

        $this->add(null, $option, $show);

        return $this;
    }

    public function isRoot()
    {
        return is_null($this->parent);
    }

    public function getRoot()
    {
        $parent = $this->parent;
        do {
            $root = $parent;
        } while ($parent && ($parent = $parent->getParent()));

        return $root;
    }

    public function getName()
    {
        return $this->menu;
    }

    public function getKey()
    {
        return $this->menuKey;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasChild()
    {
        return count($this->childs) > 0;
    }

    public function childCount()
    {
        return count($this->childs);
    }

    public function getChild()
    {
        return $this->childs;
    }

    public function setDefault(array $default)
    {
        $this->default = $default + [
            'list'=>[],
            'link'=>[],
            'divider'=>['class'=>'divider','role'=>'separator'],
        ];

        return $this;
    }

    public function setActiveClass($class)
    {
        $this->activeClass = $class;

        return $this;
    }

    public function setActiveRoute($route)
    {
        $this->activeRoute = $route;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getActiveClass()
    {
        return $this->activeClass;
    }

    public function isHidden()
    {
        return !$this->show;
    }

    public function isActive()
    {
        return $this->option['identifier']?$this->menuKey === $this->activeRoute :
            $this->option['route'] === $this->activeRoute;
    }

    public function hasActiveChild()
    {
        if (!$this->childActive) {
            foreach ($this->childs as $menu => $child) {
                if ($child->isActive()) {
                    return $this->childActive = true;
                }
            }
        }

        return false;
    }

    public function render()
    {
        $str = '';
        foreach ($this->childs as $menu => $child) {
            if ($child->isHidden()) {
                continue;
            }

            $default = $child->getDefault();
            $option = $child->getOption();

            if ($option['divider']) {
                $listContent = '';
                $option['list'] = self::mergeAttributes($option['list'], $default['divider']);
            } else {
                $active = $child->isActive() || $child->hasActiveChild();

                $option['link'] += [
                    'href'=>$option['route']?fa::path($option['route'], $option['args']):$option['url'],
                ] + $default['link'];
                $option['list'] += [
                ] + $default['list'];

                if ($active) {
                    $option['list'] = self::mergeAttributes($option['list'], ['class'=>$child->getActiveClass()]);
                }

                $childStr = $child->render();
                if ($child->hasChild() && !$childStr) {
                    continue;
                }

                $listContent = trim(self::element('a', $option['prefix'].$menu.$option['suffix'], $option['link']).PHP_EOL.$childStr);
            }

            $str .= self::element('li', $listContent, $option['list']).PHP_EOL;
        }

        return $str?self::element('ul', PHP_EOL.$str, $this->option['attr']):'';
    }
}

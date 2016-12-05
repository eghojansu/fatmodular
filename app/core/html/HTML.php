<?php

namespace App\core\html;

use Prefab;

class HTML extends Prefab
{
    public static function element($element, $content, array $attrs = [])
    {
        $str = '<'.$element.static::renderAttributes($attrs).'>'.$content.'</'.$element.'>';

        return $str;
    }

    protected static function renderAttributes(array $attrs)
    {
        $str = '';
        foreach ($attrs as $key => $value) {
            if (false===$value) {
                continue;
            }
            $str .= ' '.(is_numeric($key)?$value:$key.'="'.$value.'"');
        }

        return $str;
    }

    /**
     * Merge b to a
     *
     * @param  array
     * @param  array
     * @return array
     */
    protected static function mergeAttributes(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key])) {
                $a[$key] .= ' '.$value;
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}

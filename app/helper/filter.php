<?php

/**
 * Template filter
 */
class filter
{
    public static function getFilters()
    {
        return [
            'rupiah'=>'rupiah',
            'bool'=>'bool',
            'rdate'=>'rdate',
            'rdtime'=>'rdatetime',
            'age'=>'age',
        ];
    }

    public static function rupiah($val, $prefix = 'Rp ')
    {
        return is_numeric($val)?$prefix.number_format($val, 2, ',', '.'):null;
    }

    public static function bool($val)
    {
        return Base::instance()->get($val?'boolean.yes':'boolean.no');
    }

    public static function rdate($date)
    {
        $date = array_filter(explode('-', $date));
        krsort($date);

        return $date?implode('-', $date):null;
    }

    public static function rdatetime($date, $format = 'd-m-Y H:i:s')
    {
        if (!$date) {
            return null;
        }

        $date = new DateTime($date);

        return $date->format($format);
    }

    public static function age($date, $format = '%y')
    {
        if (!$date) {
            return null;
        }

        $now = new DateTime;
        $date = new DateTime($date);
        $diff = $now->diff($date);

        return $diff->format($format);
    }
}

<?php

/**
 * Global helper
 */
final class fa
{
    private static $db;
    private static $suffix = '.html';

    /**
     * Get connection
     *
     * @return DB\SQL
     */
    public static function db()
    {
        if (!self::$db) {
            $base = Base::instance();
            $db = $base->get('app.mysql');

            try {
                self::$db = new DB\SQL("mysql:host=$db[host];dbname=$db[name]", $db['user'], $db['password']);
            } catch (Exception $e) {
                $message = "Cannot create database connection, please review your configuration!";
                if ($base->get('DEBUG')) {
                    $message .= PHP_EOL.$e->getMessage();
                }
                $base->error(500, $message);
            }
        }

        return self::$db;
    }

    /**
     * Convert namespace to table name
     *
     * @param  string $namespace
     * @return string
     */
    public static function table_name($namespace)
    {
        return Base::instance()->snakecase(lcfirst(substr($namespace, 1+((int) strrpos($namespace, '\\')))));
    }

    /**
     * Handle file upload, cannot handle multiple files
     *
     * @param  string $key          $_FILES[$key]
     * @param  string &$filename
     * @param  array  $allowedTypes
     * @return bool
     */
    public static function handle_file_upload($key, &$filename, $allowedTypes = [])
    {
        $result = false;
        $isArray = isset($_FILES[$key]) && is_array($_FILES[$key]['error']);

        if ($isArray) {
            return $result;
        }

        if (isset($_FILES[$key]) &&
            UPLOAD_ERR_OK === $_FILES[$key]['error'] &&
            ($allowedTypes && in_array($_FILES[$key]['type'], $allowedTypes))) {
            $ext = strtolower(strrchr($_FILES[$key]['name'], '.'));
            $filename .= $ext;
            $result = move_uploaded_file($_FILES[$key]['tmp_name'], $filename);
        }

        return $result;
    }

    /**
     * Say number in indonesian
     * note: this function can exhaust memory if $no greater than 1000000
     * (need improvement)
     *
     * @param  float $no
     * @return string
     */
    public static function terbilang($no)
    {
        if (!is_numeric($no)) {
            return null;
        }

        $strNo = str_replace(',', '.', strval($no));
        $fraction = '0'.(false === ($pos = strpos($strNo, '.'))? '.0':substr($strNo, $pos));
        $no *= 1;
        $minus = 0 > $no;
        $cacah = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];

        $no = abs($no) - $fraction * 1;

        if ($no < 12) {
            $result = $cacah[$no];
        } elseif ($no < 20) {
            $result = $cacah[$no-10].' belas';
        } else if ($no < 100) {
            $mod = $no % 10;
            $mul = floor($no / 10);

            $result = $cacah[$mul].' puluh '.$cacah[$mod];
        } else if ($no < 1000) {
            $mod = $no % 100;
            $mul = floor($no / 100);

            $result = $cacah[$mul].' ratus '.self::terbilang($mod);
        } else if ($no < 100000) {
            $mod = $no % 1000;
            $mul = floor($no / 1000);

            $result = self::terbilang($mul).' ribu '.self::terbilang($mod);
        } else if ($no < 1000000000) {
            $mod = $no % 1000000;
            $mul = floor($no / 1000000);

            $result = self::terbilang($mul).' juta '.self::terbilang($mod);
        } else {
            return $no * ($minus?-1:1);
        }

        $result = ($minus?'minus ':'').str_replace([' nol','satu ','sejuta'], ['','se','satu juta'], $result);

        if ($fraction) {
            for ($i=2, $e=strlen($fraction), $ei=$e-1; $i < $e; $i++) {
                if (2 === $i) {
                    if ($i === $ei && '0' === $fraction[$i]) {
                        break;
                    }
                    $result .= ' koma';
                }
                $result .= ' '.$fraction[$i];
            }
        }

        return $result;
    }

    /**
     * Build path
     *
     * @param  string $path   route or url
     * @param  array  $params
     * @return string
     */
    public static function path($path, array $params = [])
    {
        $base = Base::instance();
        $PARAMS = $base->get('PARAMS');
        unset($PARAMS[0]);
        $params += $PARAMS;

        if (false === strpos($path, '/') && $p = $base->get('ALIASES.'.$path)) {
            $path = ltrim($p,'/');

            $i=0;
            $path=preg_replace_callback('/@(\w+)|\*/',
                function($match) use(&$i,&$params) {
                    $i++;
                    if (isset($match[1]) && array_key_exists($match[1],$params)) {
                        $p = $params[$match[1]];
                        unset($params[$match[1]]);

                        return $p;
                    }

                    $p = array_key_exists($i,$params)?$params[$i]:$match[0];
                    unset($params[$i]);

                    return $p;
                },$path);
            foreach ($PARAMS as $key => $value) {
                unset($params[$key]);
            }
        }

        return '#'===$path[0]?$path:$base->get('BASE').'/'.$path.($params?'?'.http_build_query($params):'');
    }

    public static function microtime($time)
    {
        if (!is_numeric($time)) {
            return null;
        }

        $strNo = str_replace(',', '.', strval($time));
        $fraction = '0'.(false === ($pos = strpos($strNo, '.'))? '.0':substr($strNo, $pos));
        $time *= 1;
        $minus = 0 > $time?'-':'';

        $time = abs($time) - $fraction * 1;
        $fraction = $fraction*1?','.substr(round($fraction, 2), 2):'';

        if ($time < 60) {
            return $minus.$time.$fraction.'s';
        }

        $limits = [
            'm'=>60,
            'h'=>3600,
            'd'=>86400,
            'w'=>604800,
            'mo'=>18144000,
        ];
        arsort($limits);
        $limitsUsed = [];

        foreach ($limits as $key => $value) {
            if ($time <= $value) {
                $limitsUsed[$key] = $value;
            }
        }

        if (count($limits) === count($limitsUsed) && $time > end($limitsUsed)) {
            return '~';
        }

        $str = [];
        foreach ($limits as $key => $value) {
            $mod = floor($time / $value);
            $time %= $value;
            if ($mod) {
                $str[] = $mod.''.$key;
            }
        }
        if ($time || $fraction) {
            $str[] = $time.$fraction.'s';
        }
        $str = implode(' ', $str);

        return $minus.$str;
    }

    public static function dump($message, $halt = true, $cleanPrevious = true)
    {
        if ($cleanPrevious) {
            ob_clean();
        }

        echo '<pre>';
        var_dump($message);
        echo '</pre>';

        if ($halt) {
            die;
        }
    }

    /**
     * Resolve view path
     *
     * @param  string $view
     * @return string
     */
    public static function view($view, $prefix = '', $suffix = '')
    {
        return str_replace('.', '/', $prefix.$view).($suffix?:self::$suffix);
    }
}

<?php

namespace App\core;

use fa;
use Base;
use DB\SQL\Mapper;

class SQLMapper extends Mapper implements CursorInterface
{
    const ERROR_NOKEY = 'No keys in %s table';
    const ERROR_NOFIELD = 'No %s fields in %s table';

    const TS_CREATE = 'created_at';
    const TS_UPDATE = 'updated_at';
    const TS_DELETE = 'deleted_at';

    /**
     * Table name
     *
     * @var  string
     */
    protected $source;

    /**
     * Primary Key
     *
     * @var  array
     */
    protected $pkey = [];

    /**
     * Constructor
     */
    public function __construct($table = null)
    {
        $db = fa::db();
        $table = $this->source?:($table?:fa::table_name(static::class));
        $fields = null;
        $ttl = 60;
        parent::__construct($db, $table, $fields, $ttl);
    }


    /**
     * Advanced cursor paginate
     *
     * @param  string|array  $filter
     * @param  array   $options
     * @param  integer $ttl
     * @return array
     */
    public function apaginate($filter = null, array $options = [], $ttl = 0)
    {
        $base = Base::instance();
        $posKey = 'GET.'.$base->get('PAGE');
        $limitKey = 'GET.'.$base->get('LIMIT');
        $sortKey = 'GET.'.$base->get('SORT');
        $limitValid = $base->get('LIMIT_LIST');

        $pos = $base->get($posKey);
            $pos = $pos > 0 ? $pos-1 : 0;
        $size = $base->get($limitKey);
        $sort = $base->get($sortKey)?:[];
        if (!$size || !in_array($size, $limitValid)) {
            $size = reset($limitValid);
        }
        $order = '';
        foreach ($sort as $key) {
            if ($key && $this->exists(str_replace([' desc',' asc'], '', strtolower($key)))) {
                $order .= ($order?',':'').$key;
            }
        }
        if ($order) {
            $options['order'] = (isset($options['order'])?$options['order'].',':'').$order;
        }

        return self::paginate($pos, $size, $filter, $options, $ttl);
    }

    /**
     * @see parent
     */
    public function getPrimaryKey()
    {
        $this->resolvePkey();

        return $this->pkey;
    }

    /**
     * @see parent
     */
    public function getPrevious($field)
    {
        return $this->fields[$field]['initial'];
    }

    /**
     * @see parent
     */
    public function loadByKey($vals, $filter = null, array $options = null, $ttl = 0, $useFind = false)
    {
        $this->resolvePkey();

        $pkeys = array_combine(
            is_array($this->pkey)?$this->pkey:[$this->pkey],
            is_array($vals)?$vals:[$vals]
        );

        $filter = $this->combineFilter($pkeys, $filter);

        if ($useFind) {
            return $this->find($filter, $options, $ttl);
        }

        $this->load($filter, $options, $ttl);

        return $this;
    }

    /**
     * @see parent
     */
    public function loadBy(array $fields, array $options = null, $ttl = 0, $useFind = false)
    {
        $filter = $this->combineFilter($fields);

        if ($useFind) {
            return $this->find($filter, $options, $ttl);
        }

        $this->load($filter, $options, $ttl);

        return $this;
    }

    /**
     * Update timestamp value
     *
     * @return $this
     */
    public function updateTimestamp()
    {
        if ($this->checkTS([static::TS_CREATE], false) && $this->dry()) {
            $this->set(static::TS_CREATE, date('Y-m-d H:i:s'));
        }
        if ($this->checkTS([static::TS_UPDATE], false) && $this->valid()) {
            $this->set(static::TS_UPDATE, date('Y-m-d H:i:s'));
        }

        return $this;
    }

    /**
     * Perform soft erase
     *
     * @param  string|array $filter
     * @return bool
     */
    public function softErase($filter = null)
    {
        $this->checkTS([static::TS_DELETE]);

        if ($filter) {
            $filter = $this->combineFilter([], $filter);

            $sql = sprintf('update %s set %s = "%s" where %s',
                $this->table,
                $this->db->quotekey(static::TS_DELETE),
                date('Y-m-d H:i:is'),
                array_shift($filter)
            );

            return $this->db->exec($sql, $filter);
        }

        $this->set(static::TS_DELETE, date('Y-m-d H:i:s'));
        $out = $this->update();

        return $out;
    }

    /**
     * Restore deleted
     *
     * @param  string|array $filter
     * @return bool
     */
    public function restore($filter = null)
    {
        $this->checkTS([static::TS_DELETE]);

        if ($filter) {
            $filter = $this->combineFilter([], $filter);

            $sql = sprintf('update %s set %s = null where %s',
                $this->table,
                $this->db->quotekey(static::TS_DELETE),
                array_shift($filter)
            );

            return $this->db->exec($sql, $filter);
        }

        $this->set(static::TS_DELETE, null);
        $out = $this->update();

        return $out;
    }

    /**
     * Clear trash
     *
     * @return bool
     */
    public function clearTrash()
    {
        $this->checkTS([static::TS_DELETE]);

        $filter = sprintf('%s is not null', $this->db->quotekey(static::TS_DELETE));

        return $this->erase($filter);
    }

    /**
     * Generate new ID based on format
     *
     * @param string $columName
     * @param string $format
     * @param string|boolean $assign
     * @param string|array $filter
     * @return object|string
     */
    public function nextID($columnName, $format, $assign = false, $filter = null)
    {
        $dateFilter = [];
        $boundPattern = '/\{([a-z0-9\- _\.]+)\}/i';
        $date = null;
        $start = 0;
        $count = 0;
        $pattern = preg_replace_callback($boundPattern, function($match) use (&$date, &$start, &$count) {
            if (is_numeric($match[1])) {
                return '(?<serial>'.str_replace('9', '[0-9]', $match[1]).')';
            }

            $date = date($match[1]);
            $start = strpos($format, $match[1]);
            $count = strlen($date);

            return '(?<date>.{'.$count.'})';
        }, $format);
        if ($date) {
            $dateFilter = ["substr($columnName,$start,$count) = :date", ':date'=>$date];
        }
        if ($dateFilter) {
            $filter[0] .= ' and '.array_shift($dateFilter);
            $filter = array_merge($dateFilter);
        }

        $clone = clone $this;
        $clone->load($filter, [
            'limit'=>1,
            'order'=>$columnName.' desc',
            ]);

        $last = 0;
        if ($clone->valid()) {
            if (preg_match('/^'.$pattern.'$/i', $clone[$columnName], $match)) {
                $last = $match['serial']*1;
            }
        }

        $id = preg_replace_callback($boundPattern, function($match) use ($last) {
            return is_numeric($match[1])?
                str_pad($last+1, strlen($match[1]), '0', STR_PAD_LEFT):
                date($match[1]);
        }, $format);

        if ($assign) {
            $this->set(is_string($assign)?$assign:$columnName, $id);

            return $this;
        }

        return $id;
    }

    /**
     * Populate record and transform to key=value pair array
     *
     * @param  string $key      column name as key
     * @param  string|callable|null|array $value    column name as value
     * @param  array|string  $filter
     * @param  array $options
     * @param  int $ttl
     * @return array
     */
    public function populate($key, $value = null, $filter = null, array $options = [], $ttl = 0)
    {
        $data = [];
        $records = $this->find($filter, $options, $ttl);
        foreach ($records as $record) {
            if (is_null($value)) {
                $v = $record[$key];
            } elseif (is_array($value)) {
                if (empty($value)) {
                    $v = $record->cast();
                } else {
                    $v = [];
                    foreach ($value as $k) {
                        if (!$record->exists($k)) {
                            user_error(sprintf(self::ERROR_NOFIELD, $k, $this->source));
                        }
                        $v[$k] = $record[$k];
                    }
                }
            } elseif (is_callable($value)) {
                $v = call_user_func_array($value, [$record]);
            } else {
                if (!$record->exists($value)) {
                    user_error(sprintf(self::ERROR_NOFIELD, $value, $this->source));
                }
                $v = $record[$value];
            }
            $data[$record[$key]] = $v;
        }

        return $data;
    }

    /**
     * Define references, just another shortcut to load
     *
     * @param  string  $class   class name
     * @param  string pair id=foreign_id
     * @param  integer $ttl
     * @return $this
     */
    protected function hasOne($class, $map, $ttl = 0)
    {
        $map = explode('=', $map);

        $mapper = new $class;
        $field = [
            $map[0]=>$this->get($map[1]),
        ];
        $mapper->loadBy($field, null, null, $ttl);

        return $mapper;
    }

    /**
     * Shortcut for loadBy
     *
     * @param  string  $class
     * @param  string pair id=foreign_id
     * @param  integer $ttl
     * @return $this|array
     */
    protected function hasMany($class, $map, $ttl = 0, $useFind = false)
    {
        $map = explode('=', $map);

        $mapper = new $class;
        $field = [
            $map[0]=>$this->get($map[1]),
        ];

        return $mapper->loadBy($field, null, null, $ttl, $useFind);
    }

    /**
     * Resolve primary key
     */
    protected function resolvePkey()
    {
        if (!$this->pkey) {
            foreach ($this->fields as $key => $field) {
                if ($field['pkey']) {
                    $this->pkey[] = $key;
                }
            }

            if (!$this->pkey) {
                user_error(sprintf(self::ERROR_NOKEY, $this->source));
            }
        }
    }

    /**
     * Check Timestamp
     *
     * @param  array   $checks
     * @param  boolean $triggerError
     * @return boolean
     */
    protected function checkTS(array $checks, $triggerError = true)
    {
        foreach ($checks as $key) {
            if (!array_key_exists($key, $this->fields)) {
                if ($triggerError) {
                    user_error(sprintf(self::ERROR_NOFIELD, $key, $this->source));
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Combine fields and filter
     *
     * @param  array  $fields
     * @param  string|array $filter
     * @param  string $join
     * @return array
     */
    protected function combineFilter(array $fields, $filter = null, $join = 'and')
    {
        $filterKey = [''];
        $mode = ($filter && false===strpos($filter[0], '?'))?':':'?';
        foreach ($fields as $key => $value) {
            $filterKey[0] .= ($filterKey[0]?' and ':'').$this->db->quotekey($key).' = ';
            if (':' === $mode) {
                $kkey = ':_key_'.$key;
                $filterKey[0] .= $kkey;
                $filterKey[$kkey] = $value;
            }
            else {
                $filterKey[0] .= '?';
                $filterKey[] = $value;
            }
        }

        if ($filter) {
            if (is_string($filter)) {
                $filter = [$filter];
            }
            if ($filterKey[0]) {
                $filter[0] .= ' '.$join.' ('.array_shift($filterKey).')';
                $filter = array_merge($filter, $filterKey);
            }
        } else {
            $filter = $filterKey;
        }

        return $filter;
    }
}

<?php

namespace App\core;

interface CursorInterface
{
    /**
     * Load mapper by Key
     *
     * @param  string|array     $vals
     * @param  string|array     $filter
     * @param  array|null $options
     * @param  integer    $ttl
     * @param  boolean    $useFind
     * @return core\CursorInterface|array
     */
    public function loadByKey($vals, $filter = null, array $options = null, $ttl = 0, $useFind = false);

    /**
     * Load by key value pair
     *
     * @param  array      $fields
     * @param  array|null $options
     * @param  integer    $ttl
     * @param  boolean    $useFind
     * @return core\CursorInterface|array
     */
    public function loadBy(array $fields, array $options = null, $ttl = 0, $useFind = false);

    /**
     * Get primary key
     *
     * @return array
     */
    public function getPrimaryKey();

    /**
     * Valid complement
     * @return boolean
     */
    public function dry();

    /**
     * Check map validity
     * @return boolean
     */
    public function valid();

    /**
     * Get previous field value
     *
     * @param  string $field
     * @return string
     */
    public function getPrevious($field);
}

<?php

namespace Acms\Services\Common;

use Acms\Contracts\Singleton;

class InjectTemplate extends Singleton
{
    /**
     * @var array
     */
    protected $collection;

    /**
     * @param string $type
     * @param string $path
     */
    public function add($type, $path)
    {
        if (!isset($this->collection[$type])) {
            $this->collection[$type] = array();
        }
        $this->collection[$type][] = $path;
    }

    /**
     * @param $type
     * @return array
     */
    public function get($type)
    {
        if (isset($this->collection[$type])) {
            return $this->collection[$type];
        }
        return array();
    }
}
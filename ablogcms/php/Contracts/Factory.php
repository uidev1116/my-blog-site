<?php

namespace Acms\Contracts;

abstract class Factory
{
    /**
     * @var mixed
     */
    protected $instance;

    public function __construct()
    {
        $this->instance = $this->createInstance();
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ( is_callable(array($this->instance, $method)) ) {
            return call_user_func_array(array($this->instance, $method), $arguments);
        }
    }

    /**
     * @return mixed
     */
    abstract public function createInstance();
}
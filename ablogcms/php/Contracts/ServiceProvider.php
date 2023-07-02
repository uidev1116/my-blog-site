<?php

namespace Acms\Contracts;

use Acms\Services\Container;

abstract class ServiceProvider
{
    /**
     * register service
     *
     * @param \Acms\Services\Container $container
     *
     * @return void
     */
    abstract public function register(Container $container);

    /**
     * initialize service
     *
     * @return void
     */
    public function init()
    {

    }
}
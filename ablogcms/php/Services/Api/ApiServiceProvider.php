<?php

namespace Acms\Services\Api;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * register service
     *
     * @param \Acms\Services\Container $container
     *
     * @return void
     */
    public function register(Container $container)
    {
        $container->bind('api-get', 'Acms\Services\Api\Engine');
    }

    /**
     * initialize service
     *
     * @return void
     */
    public function init()
    {
    }
}

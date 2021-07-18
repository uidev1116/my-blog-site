<?php

namespace Acms\Services\Http;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class HttpServiceProvider extends ServiceProvider
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
        $container->bind('http', 'Acms\Services\Http\Engine');
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
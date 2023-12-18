<?php

namespace Acms\Services\Cache;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class CacheServiceProvider extends ServiceProvider
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
        $container->singleton('cache', 'Acms\Services\Cache\CacheManager');
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
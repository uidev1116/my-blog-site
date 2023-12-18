<?php

namespace Acms\Services\React;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ReactServiceProvider extends ServiceProvider
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
        $container->singleton('react', function () {
            return new Factory(config('static_export_name_server', '8.8.8.8'));
        });
    }
}

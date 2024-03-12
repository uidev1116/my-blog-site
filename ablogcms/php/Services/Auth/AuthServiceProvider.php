<?php

namespace Acms\Services\Auth;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class AuthServiceProvider extends ServiceProvider
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
        $container->singleton('auth.role', 'Acms\Services\Auth\Role');
        $container->singleton('auth.general', 'Acms\Services\Auth\General');
        $container->singleton('auth', 'Acms\Services\Auth\Factory');
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

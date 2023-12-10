<?php

namespace Acms\Services\User;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class UserServiceProvider extends ServiceProvider
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
        $container->singleton('user', 'Acms\Services\User\Helper');
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

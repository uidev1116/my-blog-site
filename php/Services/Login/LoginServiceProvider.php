<?php

namespace Acms\Services\Login;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use ACMS_RAM;

class LoginServiceProvider extends ServiceProvider
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
        $container->singleton('login', 'Acms\Services\Login\Helper');
        $container->bind('login.tfa', function () {
            return new Engine(ACMS_RAM::blogName(BID));
        });
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

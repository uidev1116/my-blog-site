<?php

namespace Acms\Services\Common;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class CommonServiceProvider extends ServiceProvider
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
        $container->singleton('common', 'Acms\Services\Common\Helper');
        $container->singleton('common.logger', 'Acms\Services\Common\Logger');
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

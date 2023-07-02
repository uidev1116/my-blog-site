<?php

namespace Acms\Services\Module;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ModuleServiceProvider extends ServiceProvider
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
        $container->singleton('module', 'Acms\Services\Module\Helper');
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
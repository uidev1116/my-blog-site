<?php

namespace Acms\Services\View;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ViewServiceProvider extends ServiceProvider
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
        $container->bind('view', 'Acms\Services\View\Engine');
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

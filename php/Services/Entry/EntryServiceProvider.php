<?php

namespace Acms\Services\Entry;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class EntryServiceProvider extends ServiceProvider
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
        $container->singleton('entry', 'Acms\Services\Entry\Helper');
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
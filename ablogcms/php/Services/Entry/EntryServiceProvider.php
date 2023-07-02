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
        $container->singleton('entry.export', 'Acms\Services\Entry\Export');
        $container->singleton('entry.import', 'Acms\Services\Entry\Import');
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

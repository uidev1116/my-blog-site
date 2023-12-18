<?php

namespace Acms\Services\Storage;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use App;
use Config;

class StorageServiceProvider extends ServiceProvider
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
        $container->singleton('storage', 'Acms\Services\Storage\Filesystem');
    }
}
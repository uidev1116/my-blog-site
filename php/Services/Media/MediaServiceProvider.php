<?php

namespace Acms\Services\Media;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class MediaServiceProvider extends ServiceProvider
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
        $container->singleton('media', 'Acms\Services\Media\Helper');
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

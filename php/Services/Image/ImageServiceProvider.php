<?php

namespace Acms\Services\Image;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ImageServiceProvider extends ServiceProvider
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
        $container->singleton('image', 'Acms\Services\Image\Helper');
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
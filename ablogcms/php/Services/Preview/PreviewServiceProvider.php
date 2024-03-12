<?php

namespace Acms\Services\Preview;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class PreviewServiceProvider extends ServiceProvider
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
        $container->singleton('preview', function () {
            $lifetime = 60 * 60 * 48;
            return new Engine($lifetime, BASE_URL . "admin/preview_share/");
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

<?php

namespace Acms\Services\Template;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class TemplateServiceProvider extends ServiceProvider
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
        $container->singleton('template', 'Acms\Services\Template\Helper');
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

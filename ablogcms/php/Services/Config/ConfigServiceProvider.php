<?php

namespace Acms\Services\Config;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ConfigServiceProvider extends ServiceProvider
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
        $container->singleton('config', 'Acms\Services\Config\Helper');
        $container->singleton('config.export', 'Acms\Services\Config\Export');
        $container->singleton('config.import', 'Acms\Services\Config\Import');
        $container->singleton('config.export.module', 'Acms\Services\Config\ModuleExport');
        $container->singleton('config.import.module', 'Acms\Services\Config\ModuleImport');
    }
}

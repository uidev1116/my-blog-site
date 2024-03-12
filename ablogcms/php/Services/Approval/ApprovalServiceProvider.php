<?php

namespace Acms\Services\Approval;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ApprovalServiceProvider extends ServiceProvider
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
        $container->singleton('approval', 'Acms\Services\Approval\Helper');
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

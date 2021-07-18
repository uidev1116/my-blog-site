<?php

namespace Acms\Services\RichEditor;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class RichEditorServiceProvider extends ServiceProvider
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
        $container->singleton('rich-editor', 'Acms\Services\RichEditor\Helper');
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
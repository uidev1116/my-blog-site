<?php

namespace Acms\Services\Blog;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class BlogServiceProvider extends ServiceProvider
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
        $container->singleton('blog', 'Acms\Services\Blog\Helper');
        $container->singleton('blog.export', 'Acms\Services\Blog\Export');
        $container->singleton('blog.import', 'Acms\Services\Blog\Import');
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
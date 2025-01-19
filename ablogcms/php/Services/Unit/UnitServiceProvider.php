<?php

namespace Acms\Services\Unit;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use Acms\Services\Facades\Application;

class UnitServiceProvider extends ServiceProvider
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
        $container->singleton('unit-registry', 'Acms\Services\Unit\Registry');
        $container->singleton('unit-repository', function () {
            $registry = Application::make('unit-registry');
            return new Repository($registry);
        });
        $container->bind('unit-rendering-front', 'Acms\Services\Unit\Rendering\Front');
        $container->bind('unit-rendering-edit', 'Acms\Services\Unit\Rendering\Edit');
        $container->bind('unit-rendering-config', 'Acms\Services\Unit\Rendering\Config');
    }

    /**
     * initialize service
     *
     * @return void
     */
    public function init()
    {
        Application::bootstrap('unit-registry', function ($registry) {
            $registry->bind('text', 'Acms\Services\Unit\Models\Text');
            $registry->bind('media', 'Acms\Services\Unit\Models\Media');
            $registry->bind('table', 'Acms\Services\Unit\Models\Table');
            $registry->bind('youtube', 'Acms\Services\Unit\Models\YouTube');
            $registry->bind('video', 'Acms\Services\Unit\Models\Video');
            $registry->bind('eximage', 'Acms\Services\Unit\Models\ExImage');
            $registry->bind('quote', 'Acms\Services\Unit\Models\Quote');
            $registry->bind('file', 'Acms\Services\Unit\Models\File');
            $registry->bind('map', 'Acms\Services\Unit\Models\Map');
            $registry->bind('osmap', 'Acms\Services\Unit\Models\OsMap');
            $registry->bind('image', 'Acms\Services\Unit\Models\Image');
            $registry->bind('rich-editor', 'Acms\Services\Unit\Models\RichEditor');
            $registry->bind('module', 'Acms\Services\Unit\Models\Module');
            $registry->bind('break', 'Acms\Services\Unit\Models\NewPage');
            $registry->bind('custom', 'Acms\Services\Unit\Models\Custom');
        });
    }
}

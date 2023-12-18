<?php

declare(strict_types=1);

namespace Acms\Services\Shortcut;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class ShortcutServiceProvider extends ServiceProvider
{
    /**
     * ショートカットに登録できるリソースのタイプ一覧
     *
     * @var string[]
     */
    private const TYPES = ['bid', 'cid', 'rid', 'mid', 'scid', 'setid'];

    /**
     * register service
     *
     * @param \Acms\Services\Container $container
     *
     * @return void
     */
    public function register(Container $container)
    {
        $container->singleton(
            'shortcut.helper',
            'Acms\Services\Shortcut\Helper',
            [self::TYPES]
        );
        $container->singleton(
            'shortcut.repository',
            'Acms\Services\Shortcut\Repository',
            [self::TYPES]
        );
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
